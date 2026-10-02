<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerDownload\Tests\Support\MockDbHandler;
use PowerDownload\Tests\Support\RecordingDbHandler;

/**
 * Abläufe rund um die Release-Seite (3.6.0):
 * - D1: Bewertungs- und Kommentarsperre je Konto, Gäste je IP
 * - D2: Kommentar mit Weiterleitung, Meldung #pdlCommentSaved auf dem Release
 * - D3: Rücksprung nach der Anmeldung (back_release)
 * - D4/D5: Admin-Optionen je Recht
 */
class ReleaseFlowTest extends TestCase
{
    private string $incDir;

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/pdl-inc/pdl_locks.inc.php';
    }

    protected function setUp(): void
    {
        $this->incDir = dirname(__DIR__, 2) . '/pdl-inc/';

        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table,
               $user_details, $user_rights, $rendertime1, $ordner_id, $release_id, $screen_id,
               $usercenter, $show_search, $show_stats, $wrong_referer, $wrong_rights, $subfiles,
               $subdirs, $release, $pdl_download_missing, $pdl_download_release_id, $pdl_current_ordner,
               $submit, $titel, $text, $ip, $csrf_token, $pdl_ucomments_output, $pdl_prerender;

        $settings = [
            'dlspeed' => 56, 'date_format' => 'd.m.Y', 'script_file' => 'downloads.php?',
            'spages' => 10, 'perpage' => 10, 'orderby' => 'name', 'orderseq' => 'ASC',
            'enable_treeview' => 'N', 'enable_extrernadmin' => 'N', 'enable_search' => 'N',
            'enable_comments' => 'Y', 'trenn_durch' => '', 'trenn_string' => '', 'bb_code' => 'N',
            'smilies' => 'N', 'glossary' => 'N', 'badwords_releases' => 'N', 'badwords_comments' => 'N',
            'html_releases' => 'N', 'html_comments' => 'N', 'shortname' => 0,
        ];
        $template = ['file_detail' => '', 'dfiles_row' => '', 'comments' => '', 'own_footer' => ''];
        $smilies = [];
        $glossary = [];
        $badwords = [];
        $users = [2 => ['nick' => 'Jonas', 'email' => '', 'homepage' => '']];
        $alt_switch = 0;
        $page = 1;
        $list = '';
        $total = 0;
        $install = 0;
        $inadmin = 0;
        $user_details = null;
        $user_rights = ['download' => 'Y', 'vote' => 'N', 'addcomments' => 'N', 'adminaccess' => 'N'];
        $rendertime1 = microtime(true);
        $ordner_id = 0;
        $release_id = 0;
        $screen_id = 0;
        $usercenter = '';
        $show_search = 0;
        $show_stats = 0;
        $wrong_referer = 0;
        $wrong_rights = 0;
        $subfiles = 0;
        $subdirs = 0;
        $release = null;
        $pdl_download_missing = false;
        $pdl_download_release_id = 0;
        $pdl_current_ordner = null;
        $submit = 0;
        $titel = '';
        $text = '';
        $ip = '10.1.1.1';
        $pdl_ucomments_output = null;
        $pdl_prerender = null;
        $sql_table = [
            'comments' => 'pdl3_comments', 'files' => 'pdl3_files', 'iplock' => 'pdl3_iplock',
            'ordner' => 'pdl3_ordner', 'release' => 'pdl3_release', 'screens' => 'pdl3_screens',
            'user' => 'pdl3_user', 'usergroup' => 'pdl3_usergroup',
        ];
        $db_handler = new MockDbHandler();

        $_GET = [];
        $_POST = [];
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        unset($_SESSION['pdl_viewed'], $_SESSION['pdl_vote_flash'], $_SESSION['pdl_comment_flash']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $csrf_token = $_SESSION['csrf_token'];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        unset($_SESSION['pdl_comment_flash']);
    }

    private function render(string $file): string
    {
        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table,
               $user_details, $user_rights, $rendertime1, $ordner_id, $release_id, $screen_id,
               $usercenter, $show_search, $show_stats, $wrong_referer, $wrong_rights, $subfiles,
               $subdirs, $release, $pdl_download_missing, $pdl_download_release_id, $pdl_current_ordner,
               $submit, $titel, $text, $ip, $csrf_token, $pdl_ucomments_output, $pdl_prerender;

        ob_start();
        include $this->incDir . $file;
        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function releaseRow(array $overrides = []): array
    {
        return array_merge([
            'release_id' => 1, 'name' => 'Deichlicht', 'text' => 'Presets', 'released' => 'Y',
            'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'time' => 1790000000,
            'uploader' => 0, 'autor' => -1, 'autor_email' => '', 'autor_nick' => '', 'autor_homepage' => '',
        ], $overrides);
    }

    // ==================== D2: Kommentar mit Weiterleitung ====================

    #[Test]
    public function commentPauseIsCheckedPerAccount(): void
    {
        global $db_handler, $user_details, $user_rights, $release_id, $submit, $titel, $text;
        $user_details = ['nick' => 'Jonas', 'user_id' => 5];
        $user_rights['addcomments'] = 'Y';
        $release_id = 1;
        $submit = 1;
        $titel = 'Nebel';
        $text = 'Genau richtig';
        $db_handler = new class () extends RecordingDbHandler {
            public function sql_insert_id(): int
            {
                return 7;
            }
        };
        $db_handler->addResult([['release_id' => 1, 'name' => 'Deichlicht']]);

        $output = $this->render('pdl_ucomments.modul.php');

        $pause = $db_handler->queriesContaining("art='comment'");
        $this->assertCount(1, $pause);
        $this->assertStringContainsString("user_id='5'", $pause[0]);
        $this->assertStringNotContainsString('ip=', $pause[0]);
        $lock = $db_handler->queriesContaining('INSERT INTO pdl3_iplock');
        $this->assertCount(1, $lock);
        $this->assertStringContainsString("VALUES ('10.1.1.1','", $lock[0]);
        $this->assertStringContainsString("','1','5','comment')", $lock[0]);
        // Ohne Header (Tests) kein Redirect, aber Ziel und Sitzung wie nach der Weiterleitung
        $this->assertStringContainsString('href="downloads.php?release_id=1&amp;commented=1#pdlComment7"', $output);
        $this->assertSame(['release_id' => 1, 'comment_id' => 7], $_SESSION['pdl_comment_flash'] ?? null);
    }

    #[Test]
    public function bufferedPrerenderOutputIsShownInsteadOfProcessingAgain(): void
    {
        global $db_handler, $pdl_ucomments_output, $submit, $user_details, $user_rights;
        $user_details = ['nick' => 'Jonas', 'user_id' => 5];
        $user_rights['addcomments'] = 'Y';
        $submit = 1;
        $pdl_ucomments_output = '<p>Fehler aus der Vorabverarbeitung</p>';
        $db_handler = new RecordingDbHandler();

        $output = $this->render('pdl_ucomments.modul.php');

        $this->assertSame('<p>Fehler aus der Vorabverarbeitung</p>', $output);
        $this->assertSame([], $db_handler->queries, 'Der Kommentar wird nicht ein zweites Mal gespeichert.');
        $this->assertNull($pdl_ucomments_output);
    }

    #[Test]
    public function commentGuestPromptLinksBackToRelease(): void
    {
        global $release_id;
        $release_id = 3;

        $output = $this->render('pdl_ucomments.modul.php');

        $this->assertStringContainsString('href="downloads.php?usercenter=login&amp;back_release=3"', $output);
    }

    #[Test]
    public function releaseShowsCommentSavedBelowNewComment(): void
    {
        global $db_handler, $release_id, $user_details, $user_rights;
        $_SESSION['pdl_comment_flash'] = ['release_id' => 1, 'comment_id' => 4];
        $_GET['commented'] = '1';
        $release_id = 1;
        $user_details = ['nick' => 'Jonas', 'user_id' => 2];
        $user_rights['addcomments'] = 'Y';
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // Aufrufe
        $db_handler->addResult([]); // Dateien
        $db_handler->addResult([]); // Screenshots
        $db_handler->addResult([
            ['comment_id' => 3, 'user_id' => 2, 'titel' => 'Alt', 'text' => 'alt', 'time' => 1790000000],
            ['comment_id' => 4, 'user_id' => 2, 'titel' => 'Nebel', 'text' => 'neu', 'time' => 1790000100],
        ]);

        $output = $this->render('pdl_release.modul.php');

        $this->assertSame(1, substr_count($output, 'id="pdlCommentSaved"'));
        $this->assertStringContainsString('Ihr Kommentar wurde veröffentlicht.', $output);
        $saved = (int) strpos($output, 'id="pdlCommentSaved"');
        $this->assertGreaterThan((int) strpos($output, 'id="pdlComment4"'), $saved);
        $this->assertLessThan((int) strpos($output, 'id="pdlCommentForm"'), $saved);
        $this->assertArrayNotHasKey('pdl_comment_flash', $_SESSION, 'Die Meldung erscheint nur einmal.');
    }

    #[Test]
    public function commentSavedNeedsFlashOfThisRelease(): void
    {
        global $db_handler, $release_id;
        $_GET['commented'] = '1';
        $_SESSION['pdl_comment_flash'] = ['release_id' => 2, 'comment_id' => 9];
        $release_id = 1;
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // Aufrufe
        $db_handler->addResult([]); // Dateien
        $db_handler->addResult([]); // Screenshots
        $db_handler->addResult([]); // Kommentare

        $output = $this->render('pdl_release.modul.php');

        $this->assertStringNotContainsString('pdlCommentSaved', $output, 'commented=1 allein zeigt nichts an.');
        $this->assertSame(['release_id' => 2, 'comment_id' => 9], $_SESSION['pdl_comment_flash'] ?? null);
    }

    // ==================== D1 und D3 auf der Release-Seite ====================

    #[Test]
    public function guestHintsLinkBackToRelease(): void
    {
        global $db_handler, $release_id;
        $release_id = 1;
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // Aufrufe
        $db_handler->addResult([]); // Dateien
        $db_handler->addResult([]); // Screenshots
        $db_handler->addResult([]); // Kommentare

        $output = $this->render('pdl_release.modul.php');

        $this->assertStringContainsString('<a href="downloads.php?usercenter=login&amp;back_release=1" id="pdlVoteLogin">melden Sie sich an</a>, um dieses Release zu bewerten.', $output);
        $this->assertStringContainsString('<a href="downloads.php?usercenter=login&amp;back_release=1" id="pdlCommentLogin">melden Sie sich an</a>', $output);
    }

    #[Test]
    public function voteLockOnReleasePageIsPerAccount(): void
    {
        global $db_handler, $release_id, $user_details, $user_rights;
        $release_id = 1;
        $user_details = ['nick' => 'Frieda', 'user_id' => 3];
        $user_rights['vote'] = 'Y';
        $db_handler = new RecordingDbHandler();
        $db_handler->addResult([$this->releaseRow()]);

        $output = $this->render('pdl_release.modul.php');

        $this->assertSame(["SELECT file_id FROM pdl3_iplock WHERE art='vote' AND file_id='1' AND user_id='3' LIMIT 1"], $db_handler->queriesContaining("art='vote'"));
        $this->assertStringContainsString('id="pdlVoteForm"', $output);
    }

    #[Test]
    public function voteLockOnReleasePageForGuestsIsPerIp(): void
    {
        global $db_handler, $release_id, $user_rights;
        $release_id = 1;
        $user_rights['vote'] = 'Y';
        $db_handler = new RecordingDbHandler();
        $db_handler->addResult([$this->releaseRow()]);

        $this->render('pdl_release.modul.php');

        $this->assertSame(["SELECT file_id FROM pdl3_iplock WHERE art='vote' AND file_id='1' AND ip='10.1.1.1' LIMIT 1"], $db_handler->queriesContaining("art='vote'"));
    }

    #[Test]
    public function releaseConfirmsLoginAfterReturn(): void
    {
        global $db_handler, $release_id, $user_details;
        $_GET['login_ok'] = '1';
        $release_id = 1;
        $user_details = ['nick' => 'Jonas', 'user_id' => 2];
        $db_handler->addResult([$this->releaseRow()]);

        $output = $this->render('pdl_release.modul.php');

        $this->assertStringContainsString('<div id="pdlLoginOk">', $output);
        $this->assertStringContainsString('Sie sind jetzt angemeldet.</strong> Willkommen, Jonas!', $output);
        $this->assertLessThan((int) strpos($output, 'id="pdlRelease"'), (int) strpos($output, 'pdlLoginOk'));
    }

    #[Test]
    public function releaseIgnoresLoginOkForGuests(): void
    {
        global $db_handler, $release_id;
        $_GET['login_ok'] = '1';
        $release_id = 1;
        $db_handler->addResult([$this->releaseRow()]);

        $this->assertStringNotContainsString('pdlLoginOk', $this->render('pdl_release.modul.php'));
    }

    #[Test]
    public function loginFormKeepsBackRelease(): void
    {
        $_GET['back_release'] = '4';
        $this->assertStringContainsString('<input type="hidden" name="back_release" value="4">', $this->render('pdl_ulogin.modul.php'));

        // Leeres Formular abgeschickt: Rücksprungziel aus dem Formular bleiben
        $_GET = [];
        $_POST = ['login' => '1', 'nick' => '', 'pw' => '', 'back_release' => '6'];
        $this->assertStringContainsString('name="back_release" value="6"', $this->render('pdl_ulogin.modul.php'));

        $_POST = [];
        $_GET['back_release'] = '4"><script>';
        $this->assertStringNotContainsString('back_release', $this->render('pdl_ulogin.modul.php'));
    }

    // ==================== D3: Seite ohne Download-Recht ====================

    #[Test]
    public function wrongRightsPageOffersLoginWithReturnAndBackLink(): void
    {
        global $wrong_rights;
        $wrong_rights = 1;
        $_GET['back_release'] = '4';

        $output = $this->render('pdl_downloads.inc.php');

        $this->assertStringContainsString('<div id="pdlWrongRights">', $output);
        $this->assertStringContainsString('id="pdlRightsLogin" href="downloads.php?usercenter=login&amp;back_release=4"', $output);
        $this->assertStringContainsString('id="pdlRightsBack" href="downloads.php?release_id=4">Zurück zum Release</a>', $output);
    }

    #[Test]
    public function wrongRightsPageForMembersWithoutLoginLink(): void
    {
        global $wrong_rights, $user_details;
        $wrong_rights = 1;
        $user_details = ['nick' => 'Moritz', 'user_id' => 4];
        $_GET['back_release'] = '1';

        $output = $this->render('pdl_downloads.inc.php');

        $this->assertStringContainsString('Ihre Benutzergruppe darf keine Dateien herunterladen.', $output);
        $this->assertStringNotContainsString('pdlRightsLogin', $output);
        $this->assertStringContainsString('id="pdlRightsBack"', $output);
    }

    #[Test]
    public function wrongRightsPageWithoutReturnTarget(): void
    {
        global $wrong_rights;
        $wrong_rights = 1;

        $output = $this->render('pdl_downloads.inc.php');

        $this->assertStringContainsString('id="pdlRightsLogin" href="downloads.php?usercenter=login"', $output);
        $this->assertStringNotContainsString('pdlRightsBack', $output);
    }

    // ==================== D4/D5: Admin-Optionen je Recht ====================

    /**
     * @param array<string, string> $rights
     */
    private function asExternAdmin(array $rights): void
    {
        global $settings, $user_details, $user_rights;
        $settings['enable_extrernadmin'] = 'Y';
        $user_details = ['nick' => 'Moritz', 'user_id' => 4];
        $user_rights = array_merge(['download' => 'Y', 'vote' => 'N', 'addcomments' => 'N', 'adminaccess' => 'Y'], $rights);
    }

    private function runOrdner5(): string
    {
        global $db_handler, $ordner_id;
        $ordner_id = 5;
        $db_handler->addResult([['ordner_id' => 5, 'sordner_id' => 0, 'name' => 'Presets']]); // Navigationspfad
        $db_handler->addResult([['ordner_id' => 5, 'name' => 'Presets']]); // Ordner vorhanden
        $db_handler->addResult([]); // Releases
        $db_handler->addResult([]); // Unterordner
        return $this->render('pdl_downloads.inc.php');
    }

    #[Test]
    public function ordnerOptionsOfferReleaseInsteadOfFile(): void
    {
        $this->asExternAdmin(['addfiles' => 'Y', 'adddirs' => 'Y']);

        $output = $this->runOrdner5();

        $this->assertStringContainsString('<option value="addrelease.php?ordner_id=5">Release hinzufügen</option>', $output);
        $this->assertStringContainsString('<option value="adddir.php?ordner_id=5">Unterordner hinzufügen</option>', $output);
        $this->assertStringNotContainsString('addfile.php', $output);
        $this->assertStringNotContainsString('editdir.php', $output);
        // Leerer Ordner: Direkt-Link zum Release ebenfalls nur mit Recht addfiles
        $this->assertStringContainsString('pdl-admin/addrelease.php?ordner_id=5', $output);
    }

    #[Test]
    public function ordnerOptionsHiddenWithoutAnyRight(): void
    {
        $this->asExternAdmin([]);

        $output = $this->runOrdner5();

        $this->assertStringNotContainsString('pdlAdminOptionsOrdner', $output);
        $this->assertStringNotContainsString('addrelease.php', $output, 'Ohne addfiles kein „Release hier anlegen“.');
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: list<string>}>
     */
    public static function releaseRightsProvider(): array
    {
        return [
            'nur bearbeiten' => [['editfiles' => 'Y'], ['editrelease.php', 'addfile.php', 'addscreen.php']],
            'nur hinzufügen' => [['addfiles' => 'Y'], ['addfile.php', 'addscreen.php']],
            'nur löschen' => [['delfiles' => 'Y'], ['delrelease.php']],
            'alle' => [['addfiles' => 'Y', 'editfiles' => 'Y', 'delfiles' => 'Y'], ['editrelease.php', 'addfile.php', 'addscreen.php', 'delrelease.php']],
            'keine' => [[], []],
        ];
    }

    /**
     * @param array<string, string> $rights
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('releaseRightsProvider')]
    public function releaseOptionsFollowSingleRights(array $rights, array $expected): void
    {
        global $db_handler, $release_id, $settings;
        $this->asExternAdmin($rights);
        $settings['enable_comments'] = 'N';
        $release_id = 1;
        $db_handler->addResult([['release_id' => 1, 'name' => 'Deichlicht', 'ordner_id' => 0, 'released' => 'Y']]); // Navigationspfad
        $db_handler->addResult([$this->releaseRow()]);

        $output = $this->render('pdl_downloads.inc.php');

        preg_match_all('/<option value="(\w+\.php)\?release_id=1">/', $output, $m);
        $this->assertSame($expected, $m[1]);
        $this->assertSame($expected !== [], str_contains($output, 'id="pdlAdminOptionsRelease"'));
    }
}
