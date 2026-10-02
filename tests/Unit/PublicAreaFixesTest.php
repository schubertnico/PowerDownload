<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerDownload\Tests\Support\RecordingDbHandler;

/**
 * Tests zu den Korrekturen im öffentlichen Bereich (3.6.0): BBCode-Links,
 * E-Mail-Schutz, Kürzen, Suche, Downloads, Statistik, Widgets, Vorlagen.
 */
class PublicAreaFixesTest extends TestCase
{
    private string $incDir;

    protected function setUp(): void
    {
        $this->incDir = dirname(__DIR__, 2) . '/pdl-inc/';
        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table,
               $user_details, $user_rights, $submit, $text, $in;

        $settings = [
            'script_file' => 'downloads.php?', 'date_format' => 'd.m.Y', 'spages' => 10,
            'perpage' => 10, 'orderby' => 'name', 'orderseq' => 'ASC', 'top_count' => 5,
            'enable_search' => 'Y', 'enable_comments' => 'Y', 'trenn_durch' => '', 'trenn_string' => '',
            'trenn_zeichen' => 95, 'bb_code' => 'Y', 'smilies' => 'N', 'glossary' => 'N',
            'badwords_releases' => 'N', 'badwords_comments' => 'N', 'html_releases' => 'N',
            'html_comments' => 'N', 'shortname' => 0, 'installed' => time() - 86400 * 4,
        ];
        $template = [];
        $smilies = [];
        $glossary = [];
        $badwords = [];
        $users = [];
        $alt_switch = 0;
        $page = 1;
        $list = '';
        $total = 0;
        $install = 0;
        $inadmin = 0;
        $user_details = null;
        $user_rights = ['download' => 'Y', 'vote' => 'N', 'addcomments' => 'N', 'adminaccess' => 'N'];
        $submit = 0;
        $text = '';
        $in = 'texttitel';
        $sql_table = [
            'comments' => 'pdl3_comments', 'files' => 'pdl3_files', 'iplock' => 'pdl3_iplock',
            'ordner' => 'pdl3_ordner', 'release' => 'pdl3_release', 'replacements' => 'pdl3_replacements',
            'screens' => 'pdl3_screens', 'settings' => 'pdl3_settings', 'template' => 'pdl3_template',
            'user' => 'pdl3_user', 'usergroup' => 'pdl3_usergroup',
        ];
        $db_handler = new RecordingDbHandler();
        unset($GLOBALS['pdl_is_start_page']);
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
    }

    private function includeFile(string $file): string
    {
        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table,
               $user_details, $user_rights, $submit, $text, $in;

        ob_start();
        include $this->incDir . $file;
        return (string) ob_get_clean();
    }

    // ==================== pdl_safe_url() / BBCode (P19) ====================

    /**
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function safeUrlProvider(): array
    {
        return [
            'https' => ['https://example.org/a?b=1', 'https://example.org/a?b=1'],
            'http' => ['http://example.org', 'http://example.org'],
            'mailto' => ['mailto:info@example.org', 'mailto:info@example.org'],
            'ohne Schema' => ['example.org/pfad', 'http://example.org/pfad'],
            'Host mit Port' => ['example.org:8080/x', 'http://example.org:8080/x'],
            'relativ' => ['downloads.php?release_id=2', 'downloads.php?release_id=2'],
            'protokoll-relativ' => ['//cdn.example.org/x', 'https://cdn.example.org/x'],
            'javascript' => ['javascript:alert(1)', null],
            'JavaScript gemischt' => ['JaVaScRiPt:alert(1)', null],
            'javascript als Entity' => ['java&#115;cript:alert(1)', null],
            'data' => ['data:text/html;base64,PHNjcmlwdD4=', null],
            'vbscript' => ['vbscript:msgbox', null],
            'Steuerzeichen' => ["java\tscript:alert(1)", null],
            'Anführungszeichen' => ['https://x.org/"onmouseover="alert(1)', null],
            'leer' => ['', null],
        ];
    }

    #[Test]
    #[DataProvider('safeUrlProvider')]
    public function safeUrlAllowsOnlyHttpHttpsMailto(string $input, ?string $expected): void
    {
        $this->assertSame($expected, pdl_safe_url($input));
    }

    #[Test]
    public function bbcodeRejectsDangerousSchemesAndKeepsText(): void
    {
        $html = bbcode('[url=javascript:alert(1)]Klick[/url] [url]javascript:alert(2)[/url] [img]data:image/svg+xml,x[/img] x javascript://%0Aalert(3)', 'N', 'N', 'N', 'Y', 'N');
        $this->assertStringNotContainsString('href="javascript', $html);
        $this->assertStringNotContainsString('src="data', $html);
        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringContainsString('Klick', $html);
    }

    #[Test]
    public function bbcodeLinksGetSafeAttributes(): void
    {
        $html = bbcode('[url=https://example.org/?a=1&b=2]Seite[/url] und www.example.org [url=https://x.org/"onmouseover="x]Trick[/url]', 'N', 'N', 'N', 'Y', 'N');
        $this->assertStringContainsString('<a href="https://example.org/?a=1&amp;b=2" target="_blank" rel="noopener nofollow ugc">Seite</a>', $html);
        $this->assertStringNotContainsString('onmouseover="', $html);
        $this->assertStringContainsString('Trick', $html);
        $this->assertStringContainsString('href="http://www.example.org"', $html);
    }

    #[Test]
    public function bbcodeImageAllowsOnlyHttp(): void
    {
        $this->assertStringContainsString('<img src="https://example.org/a.png"', bbcode_apply_tags('[img]https://example.org/a.png[/img]'));
        $this->assertSame('mailto:x@y.de', bbcode_apply_tags('[img]mailto:x@y.de[/img]'));
    }

    #[Test]
    public function bbcodeEmailTagIsValidated(): void
    {
        $this->assertStringContainsString('href="mailto:info@example.org"', bbcode_apply_tags('[email]info@example.org[/email]'));
        $this->assertSame('kein-mail" onclick="x', bbcode_apply_tags('[email]kein-mail" onclick="x[/email]'));
    }

    // ==================== E-Mail-Schutz (F09) ====================

    #[Test]
    public function protectEmailsEncodesEveryAddressSeparately(): void
    {
        $result = bbcode_protect_emails('info@verein-a.de oder kasse@verein-b.de');
        $this->assertStringNotContainsString('@', $result);
        $decoded = html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertSame('info@verein-a.de oder kasse@verein-b.de', $decoded);
    }

    #[Test]
    public function bbcodeKeepsBothMailtoTargets(): void
    {
        $html = bbcode(' info@verein-a.de und kasse@verein-b.de', 'N', 'N', 'N', 'Y', 'N');
        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertStringContainsString('href="mailto:info@verein-a.de"', $decoded);
        $this->assertStringContainsString('href="mailto:kasse@verein-b.de"', $decoded);
    }

    // ==================== Kürzen (P39/F28/T11) ====================

    #[Test]
    public function truncateIsUtf8SafeAndCutsAtWordBoundary(): void
    {
        $this->assertSame('Größere Änderungen…', pdl_truncate('Größere Änderungen über Übergrößen', 22));
        $this->assertSame('Kurz', pdl_truncate('Kurz', 10));
        // Kein Leerraum in der ersten Hälfte: hart kürzen, aber nie mitten im Umlaut
        $result = pdl_truncate('Donaudampfschifffahrtsgesellschaftsöffnungszeiten', 36);
        $this->assertTrue(mb_check_encoding($result, 'UTF-8'));
        $this->assertSame(37, mb_strlen($result, 'UTF-8'));
        // Halbe Entity wird entfernt
        $this->assertSame('A und B…', pdl_truncate('A und B &amp; C', 9));
    }

    #[Test]
    public function teaserUsesMarkerOrPlainTextCut(): void
    {
        $marker = ['trenn_durch' => 'string', 'trenn_string' => '{trenn}'];
        $this->assertSame('[b]Kurz[/b] davor', pdl_teaser('[b]Kurz[/b] davor {trenn} danach', $marker));

        $chars = ['trenn_durch' => 'zeichen', 'trenn_zeichen' => 20, 'trenn_string' => '{trenn}'];
        $this->assertSame('[b]Kurz[/b]', pdl_teaser('[b]Kurz[/b]', $chars), 'kurze Texte bleiben formatiert');
        $this->assertSame('Fett und weiterer…', pdl_teaser('[b]Fett[/b] und weiterer Text mit Marke', $chars));

        $html = $chars + ['html_releases' => 'Y'];
        $this->assertSame('Fett und weiterer…', pdl_teaser('<strong>Fett</strong> und weiterer Text mit Marke', $html));
        $this->assertSame('', pdl_teaser('   ', $chars));
    }

    // ==================== Hervorheben (P16) ====================

    #[Test]
    public function highlightKeepsUmlautsTagsAndEntities(): void
    {
        $html = htmlspecialchars('Audit Größenprüfung & mehr', ENT_QUOTES, 'UTF-8');
        $result = pdl_highlight($html, ['größe', 'amp']);
        $this->assertSame('Audit <mark>Größe</mark>nprüfung &amp; mehr', $result);
        $this->assertSame('<b>x</b>', pdl_highlight('<b>x</b>', ['b']));
        $this->assertSame('ohne', pdl_highlight('ohne', []));
    }

    // ==================== ORDER BY-Whitelist (P18) ====================

    #[Test]
    public function orderSqlUsesWhitelistOnly(): void
    {
        $this->assertSame(' ORDER BY name ASC, release_id ASC', pdl_release_order_sql('name', 'ASC'));
        $this->assertSame(' ORDER BY time DESC, name ASC, release_id ASC', pdl_release_order_sql('time', 'desc'));
        $this->assertSame(' ORDER BY name ASC, release_id ASC', pdl_release_order_sql('name,(SELECT SLEEP(5))', 'ASC; DROP'));
        $this->assertStringContainsString('NULLIF(r.votes, 0)', pdl_release_order_sql('voted/votes', 'DESC', 'r'));
    }

    // ==================== Downloads (P07/P09) ====================

    #[Test]
    public function downloadTargetChecksExistenceAndLocation(): void
    {
        $root = sys_get_temp_dir() . '/pdl_dl_' . bin2hex(random_bytes(4));
        mkdir($root . '/pdl-files/3', 0777, true);
        mkdir($root . '/pdl-gfx');
        file_put_contents($root . '/pdl-files/3/Größe Test.zip', 'PK');
        file_put_contents($root . '/pdl-gfx/a.gif', 'GIF');
        file_put_contents(dirname($root) . '/pdl_outside_' . basename($root) . '.txt', 'x');

        try {
            $local = pdl_download_target('pdl-files/3/Gr%C3%B6%C3%9Fe%20Test.zip', $root);
            $this->assertSame('local', $local['type'] ?? null);
            $this->assertSame(realpath($root . '/pdl-files/3/Größe Test.zip'), $local['path'] ?? null);

            $this->assertSame(['type' => 'redirect', 'url' => 'pdl-gfx/a.gif'], pdl_download_target('pdl-gfx/a.gif', $root));
            $this->assertSame(['type' => 'redirect', 'url' => 'https://example.org/x.zip'], pdl_download_target('https://example.org/x.zip', $root));
            $this->assertNull(pdl_download_target('pdl-files/3/fehlt.zip', $root));
            $this->assertNull(pdl_download_target('../pdl_outside_' . basename($root) . '.txt', $root));
            $this->assertNull(pdl_download_target('javascript:alert(1)', $root));
            $this->assertNull(pdl_download_target('', $root));
        } finally {
            unlink($root . '/pdl-files/3/Größe Test.zip');
            unlink($root . '/pdl-gfx/a.gif');
            unlink(dirname($root) . '/pdl_outside_' . basename($root) . '.txt');
            rmdir($root . '/pdl-files/3');
            rmdir($root . '/pdl-files');
            rmdir($root . '/pdl-gfx');
            rmdir($root);
        }
    }

    #[Test]
    public function contentDispositionHasAsciiAndUtf8Name(): void
    {
        $this->assertSame(
            'attachment; filename="Groesse_Test.zip"; filename*=UTF-8\'\'Gr%C3%B6%C3%9Fe%20Test.zip',
            pdl_content_disposition('Größe Test.zip')
        );
    }

    // ==================== Suche (P16/P17/P18) ====================

    #[Test]
    public function searchFiltersByAreaAndHidesHiddenReleases(): void
    {
        global $db_handler, $submit, $text, $in, $settings;
        $submit = 1;
        $text = 'Audit Werkzeuge';
        $in = 'text';
        $settings['orderby'] = "name; DROP TABLE pdl3_user";
        $db_handler->addResult([['c' => 0]]);

        $this->includeFile('pdl_search.modul.php');
        $count = $db_handler->queriesContaining('COUNT(*)');
        $this->assertCount(1, $count);
        $this->assertStringContainsString("(text LIKE '%Audit%' AND text LIKE '%Werkzeuge%') AND released='Y'", $count[0]);
        $this->assertStringNotContainsString('name LIKE', $count[0]);
        $this->assertStringNotContainsString('DROP', implode("\n", $db_handler->queries));
    }

    #[Test]
    public function searchKeepsTermWhilePagingAndHighlightsUmlauts(): void
    {
        global $db_handler, $submit, $text, $in, $settings, $page;
        $submit = 1;
        $text = 'Größe';
        $in = 'titel';
        $page = 2;
        $settings['perpage'] = 1;
        $settings['orderby'] = 'views';
        $settings['orderseq'] = 'DESC';
        $db_handler->addResult([['c' => 2]]);
        $db_handler->addResult([
            ['release_id' => 2, 'name' => 'Audit Größenprüfung', 'text' => 'Prüft Größen', 'released' => 'Y'],
        ]);
        $db_handler->addResult([['tsize' => 53, 'cnt' => 1]]);

        $output = $this->includeFile('pdl_search.modul.php');
        $this->assertStringContainsString("name LIKE '%Größe%'", $db_handler->queries[0]);
        $this->assertStringContainsString('ORDER BY views DESC, name ASC, release_id ASC LIMIT 1,1', $db_handler->queries[1]);
        $this->assertStringContainsString('text=Gr%C3%B6%C3%9Fe&amp;in=titel', $output);
        $this->assertStringContainsString('Audit <mark>Größe</mark>nprüfung', $output);
        $this->assertStringContainsString('1 Datei &middot; 53 B', $output);
        $this->assertTrue(mb_check_encoding($output, 'UTF-8'));
    }

    #[Test]
    public function searchEscapesLikeWildcardsAndRejectsShortTerms(): void
    {
        global $db_handler, $submit, $text;
        $submit = 1;
        $text = '100%_ok';
        $db_handler->addResult([['c' => 0]]);
        $this->includeFile('pdl_search.modul.php');
        $this->assertStringContainsString('100\\\\%\\\\_ok', $db_handler->queries[0]);

        $db_handler = new RecordingDbHandler();
        $text = 'a';
        $output = $this->includeFile('pdl_search.modul.php');
        $this->assertStringContainsString('mindestens 2 Zeichen', $output);
        $this->assertSame([], $db_handler->queries);
    }

    // ==================== Statistik (P20, Gastgruppe) ====================

    #[Test]
    public function statsModuleHidesHiddenReleasesAndUsesGuestGroupSetting(): void
    {
        global $db_handler, $settings;
        $settings['guest_group_id'] = '7';
        $this->includeFile('pdl_stats.modul.php');

        $ugroup = $db_handler->queriesContaining('ugroup_name');
        $this->assertStringContainsString("ugroup_id != '7'", $ugroup[0]);
        $release_queries = $db_handler->queriesContaining('pdl3_release');
        $this->assertCount(7, $release_queries);
        foreach ($release_queries as $query) {
            $this->assertStringContainsString("released='Y'", $query);
        }
        foreach ($db_handler->queriesContaining('SUM(pdl3_files.size)') as $query) {
            $this->assertStringContainsString('mirror=0', $query);
        }
    }

    // ==================== Startseiten-Widgets (P22/P23/P24) ====================

    #[Test]
    public function statsWidgetUsesBootstrapTemplateAndOldestReleaseWhenNotInstalled(): void
    {
        global $db_handler, $settings, $template;
        $settings['installed'] = '0';
        // Alte Tabellen-Vorlage einer Bestandsinstallation
        $template['stats'] = '<table bgcolor="#333333"><tr><td bgcolor="#222222">Gesamtgroesse: {size}</td></tr></table>';
        $db_handler->addResult([
            ['file_id' => 1, 'size' => 1468006, 'downloads' => 4, 'mirror' => 0],
            ['file_id' => 2, 'size' => 0, 'downloads' => 2, 'mirror' => 1],
        ]);
        $db_handler->addResult([['t' => time() - 86400 * 2 + 60]]); // ältestes Release

        $output = $this->includeFile('pdl_stats.inc.php');
        $this->assertStringContainsString("r.released='Y'", $db_handler->queries[0]);
        $this->assertStringContainsString('MIN(time)', $db_handler->queries[1]);
        $this->assertStringNotContainsString('bgcolor', $output);
        $this->assertStringNotContainsString('Gesamtgroesse', $output);
        $this->assertStringContainsString('<span>Gesamtgröße</span> <strong>1,4 MB</strong>', $output);
        $this->assertStringContainsString('<span>Dateien</span> <strong>1</strong>', $output);
        $this->assertStringContainsString('<span>Downloads</span> <strong>6</strong>', $output);
        $this->assertStringContainsString('<span>Ø Downloads pro Tag</span> <strong>3</strong>', $output);
    }

    #[Test]
    public function statsWidgetShowsHintWithoutFiles(): void
    {
        $output = $this->includeFile('pdl_stats.inc.php');
        $this->assertStringContainsString('Noch keine Dateien.', $output);
    }

    #[Test]
    public function releaseWidgetsReplaceLegacyTablesAndPluralise(): void
    {
        global $db_handler, $template;
        $template['top_box'] = '<table border="0" cellpadding="5" cellspacing="1" bgcolor="#333333" width="100%">{rows}</table>';
        $template['top_row'] = '<tr><td bgcolor="#222222">{count}. <a href="downloads.php?release_id={id}">{name}</a> ({downloads} Downloads)</td></tr>';
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'Eins <b>', 'downloads' => 1, 'votes' => 0, 'voted' => 0],
            ['release_id' => 2, 'name' => 'Zwei', 'downloads' => 1234, 'votes' => 0, 'voted' => 0],
        ]);

        $output = $this->includeFile('pdl_top.inc.php');
        $this->assertStringNotContainsString('bgcolor', $output);
        $this->assertStringContainsString('list-group-numbered', $output);
        $this->assertStringContainsString('>1 Download<', $output);
        $this->assertStringContainsString('>1.234 Downloads<', $output);
        $this->assertStringContainsString('Eins &lt;b&gt;', $output);
        $this->assertStringContainsString('id="pdlWidgetTop"', $output);
    }

    #[Test]
    public function ratedWidgetShowsVoteOutOfTen(): void
    {
        global $db_handler;
        $db_handler->addResult([['release_id' => 1, 'name' => 'Gut', 'downloads' => 0, 'votes' => 2, 'voted' => 15]]);
        $output = $this->includeFile('pdl_rated.inc.php');
        $this->assertStringContainsString('7,5/10', $output);
        $this->assertStringNotContainsString('Note:', $output);
    }

    #[Test]
    public function widgetsStayHiddenOutsideTheStartPage(): void
    {
        global $db_handler;
        $GLOBALS['pdl_is_start_page'] = false;
        $this->assertSame('', $this->includeFile('pdl_top.inc.php'));
        $this->assertSame('', $this->includeFile('pdl_stats.inc.php'));
        $this->assertSame([], $db_handler->queries);

        $GLOBALS['pdl_is_start_page'] = true;
        $this->assertTrue(pdl_show_dashboard_widgets());
        unset($GLOBALS['pdl_is_start_page']);
    }

    #[Test]
    public function dashboardFallbackChecksPostToo(): void
    {
        $_POST['usercenter'] = 'register';
        $this->assertFalse(pdl_show_dashboard_widgets());
        $this->assertSame('Registrieren', pdl_layout_resolve_title('Download Center'));
        unset($_POST['usercenter']);
        $this->assertTrue(pdl_show_dashboard_widgets());
        $_GET['usercenter'] = 'comments';
        $this->assertSame('Kommentar schreiben', pdl_layout_resolve_title('Download Center'));
        unset($_GET['usercenter']);
    }

    // ==================== Vorlagen und Schema ====================

    #[Test]
    public function templateFallsBackForMissingOrLegacyValues(): void
    {
        global $template;
        $defaults = pdl_default_templates();
        $template = ['top_row' => '<li>{name}</li>', 'top_box' => '<table bgcolor=\"#333333\">{rows}</table>'];
        $this->assertSame('<li>{name}</li>', pdl_template('top_row'));
        $this->assertSame($defaults['top_box'], pdl_template('top_box'));
        $this->assertSame($defaults['stats'], pdl_template('stats'));
        $this->assertTrue(pdl_template_is_legacy('<td bgcolor="#222222">'));
        $this->assertFalse(pdl_template_is_legacy('<td class="x">'));
    }

    #[Test]
    public function schemaTemplatesMatchBuiltInDefaults(): void
    {
        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/pdl-inc/pdl3_schema.sql');
        foreach (pdl_default_templates() as $name => $value) {
            $pattern = '/^INSERT INTO `pdl3_template` .*? VALUES \(\d+,\'' . preg_quote($name, '/') . '\',\'(?:[^\'\\\\]|\\\\.)*\',\'(?:[^\'\\\\]|\\\\.)*\',\'textarea\',\'((?:[^\'\\\\]|\\\\.)*)\',\d+,\d+\);$/m';
            $this->assertSame(1, preg_match($pattern, $schema, $m), 'Vorlage ' . $name . ' fehlt im Schema');
            $stored = strtr($m[1], ['\\n' => "\n", '\\"' => '"', "\\'" => "'", '\\\\' => '\\']);
            $this->assertSame($value, $stored, 'Vorlage ' . $name . ' weicht vom Code ab');
        }
    }

    #[Test]
    public function schemaHasDownloadLockAndScreenshotColumns(): void
    {
        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/pdl-inc/pdl3_schema.sql');
        $this->assertMatchesRegularExpression("/CREATE TABLE `pdl3_iplock` \\(\\s*`ip` varchar\\(45\\)/", $schema);
        $this->assertMatchesRegularExpression("/`art` enum\\('comment','vote','login','register','lostpw','download'\\)/", $schema);
        $this->assertMatchesRegularExpression('/CREATE TABLE `pdl3_screens` \\([^;]*`text` varchar\\(255\\) NOT NULL DEFAULT \'\'[^;]*`views` int NOT NULL DEFAULT \'0\'/s', $schema);
    }

    // ==================== Kleinkram ====================

    #[Test]
    public function commentFormatHintNamesAllowedFormatting(): void
    {
        $this->assertSame('BBCode und Smilies sind erlaubt, HTML nicht.', pdl_comment_format_hint(['bb_code' => 'Y', 'smilies' => 'Y', 'html_comments' => 'N']));
        $this->assertSame('BBCode ist erlaubt, HTML nicht.', pdl_comment_format_hint(['bb_code' => 'Y']));
        $this->assertSame('BBCode, Smilies und HTML sind erlaubt.', pdl_comment_format_hint(['bb_code' => 'Y', 'smilies' => 'Y', 'html_comments' => 'Y']));
        $this->assertSame('Nur Text, HTML ist nicht erlaubt.', pdl_comment_format_hint([]));
    }

    #[Test]
    public function voteFormatUsesComma(): void
    {
        $this->assertSame('8', pdl_format_vote(8, 1));
        $this->assertSame('7,5', pdl_format_vote(15, 2));
        $this->assertSame('0', pdl_format_vote(0, 0));
    }

    #[Test]
    public function screenFileReturnsEmptyForMissingImage(): void
    {
        $this->assertSame('', pdl_screen_file(['release_id' => 1, 'screen_id' => 999999], 'k', dirname(__DIR__, 2)));
    }
}
