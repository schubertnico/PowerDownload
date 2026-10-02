<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Tests\Support\MockDbHandler;

class ModulesTest extends TestCase
{
    private string $incDir;

    protected function setUp(): void
    {
        $this->incDir = dirname(__DIR__, 2) . '/pdl-inc/';
        $this->setupGlobals();
        $this->seedCsrfToken();
    }

    /**
     * Seed eines CSRF-Tokens, sodass Test-Submits CSRF-Prüfung passieren.
     * Tests, die explizit die CSRF-Ablehnung prüfen, können `$csrf_token` auf
     * '' setzen.
     */
    private function seedCsrfToken(): void
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        global $csrf_token;
        $csrf_token = $_SESSION['csrf_token'];
    }

    private function setupGlobals(): void
    {
        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table,
               $user_details, $user_rights, $rendertime1;

        $settings = [
            'dlspeed' => 56,
            'date_format' => 'd.m.Y',
            'script_file' => 'downloads.php?',
            'spages' => 10,
            'perpage' => 10,
            'orderby' => 'name',
            'orderseq' => 'ASC',
            'top_count' => 5,
            'enable_search' => 'Y',
            'enable_comments' => 'N',
            'enable_treeview' => 'N',
            'enable_extrernadmin' => 'N',
            'trenn_durch' => '',
            'trenn_string' => '',
            'bb_code' => 'N',
            'smilies' => 'N',
            'glossary' => 'N',
            'badwords_releases' => 'N',
            'badwords_comments' => 'N',
            'html_releases' => 'N',
            'html_comments' => 'N',
            'referer_check' => 'N',
            'debug' => false,
            'showcopy' => false,
            'pdlversion' => 'v3.5.0',
            'shortname' => 0,
            'installed' => time() - 86400,
            'ftp_server' => '',
        ];
        $template = [
            'alt_1' => '#FFFFFF',
            'alt_2' => '#F0F0F0',
            'footer_bg' => '#CCCCCC',
            'header_bg' => '#333333',
            'table_border' => '#000000',
            'all_width' => '100%',
            'release_row' => '{name} {text}',
            'release_box' => '{rows}',
            'ordner_row' => '{name}',
            'ordner_box' => '{rows}',
            'file_detail' => '{name} {text} {autor}',
            'dfiles_row' => '{filename}',
            'comments' => '{titel} {text}',
            'top_row' => '{name}',
            'top_box' => '{rows}',
            'flop_row' => '{name}',
            'flop_box' => '{rows}',
            'latest_row' => '{name}',
            'latest_box' => '{rows}',
            'rated_row' => '{name}',
            'rated_box' => '{rows}',
            'stats' => '{files} {size} {downloads} {traffic} {durch_downloads} {durch_traffic}',
            'ulogin_form' => 'Login Form Here',
            'uregister_form' => 'Register Form Here',
            'uprofil_form' => '{email} {get_letter} {homepage} {icq}',
            'ulost_form' => 'Lost Password Form Here',
            'comments_form' => '{html} {zensur} {bbcode} {smilies} {glossar} {user}',
            'own_footer' => '',
            'mail_register' => 'Welcome {nick}',
            'mail_lost1' => 'Dear {user}, {url}',
            'mail_lost2' => 'Dear {user}, new pw: {new_pw}',
        ];
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
        $user_rights = ['download' => 'Y', 'vote' => 'Y', 'addcomments' => 'Y', 'adminaccess' => 'N'];
        $rendertime1 = microtime(true);

        $sql_table = [
            'comments' => 'pdl3_comments',
            'files' => 'pdl3_files',
            'iplock' => 'pdl3_iplock',
            'ordner' => 'pdl3_ordner',
            'release' => 'pdl3_release',
            'replacements' => 'pdl3_replacements',
            'rights' => 'pdl3_rights',
            'screens' => 'pdl3_screens',
            'settings' => 'pdl3_settings',
            'template' => 'pdl3_template',
            'user' => 'pdl3_user',
            'usergroup' => 'pdl3_usergroup',
        ];

        $db_handler = new MockDbHandler();

        // Mails nicht verschicken, sondern mitschreiben (siehe pdl_send_mail()).
        $GLOBALS['pdl_test_mails'] = [];
        $GLOBALS['pdl_mail_transport'] = static function (string $to, string $subject, string $body, array $headers): bool {
            $GLOBALS['pdl_test_mails'][] = ['to' => $to, 'subject' => $subject, 'body' => $body, 'headers' => $headers];
            return true;
        };
        $_POST = [];
    }

    /**
     * Include a module file with all globals available in scope
     */
    private function includeModule(string $file): string
    {
        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table,
               $user_details, $user_rights, $rendertime1, $ordner_id, $release_id,
               $screen_id, $usercenter, $show_search, $show_stats, $wrong_referer,
               $wrong_rights, $submit, $nick, $pw, $email, $homepage, $icq,
               $get_letter, $pw_old, $pw_new, $pw_new2, $text, $titel, $in,
               $vote, $vote_id, $remind_code, $ip, $subfiles, $subdirs,
               $showcomments, $login_error, $release, $csrf_token;

        ob_start();
        include $this->incDir . $file;
        return ob_get_clean();
    }

    // ==================== pdl_ulogin.modul.php ====================

    #[Test]
    public function loginModuleShowsForm(): void
    {
        $output = $this->includeModule('pdl_ulogin.modul.php');
        $this->assertStringContainsString('name="nick"', $output);
        $this->assertStringContainsString('name="pw"', $output);
        $this->assertStringContainsString('<form', $output);
    }

    // ==================== pdl_uregister.modul.php ====================

    #[Test]
    public function registerModuleShowsFormWhenNotLoggedIn(): void
    {
        global $user_details, $submit;
        $user_details = null;
        $submit = 0;

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('name="nick"', $output);
        $this->assertStringContainsString('name="email"', $output);
        $this->assertStringContainsString('name="pw_new"', $output);
    }

    #[Test]
    public function registerModuleShowsAlreadyLoggedIn(): void
    {
        global $user_details, $submit;
        $user_details = ['nick' => 'Test', 'user_id' => 1];
        $submit = 0;

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('bereits angemeldet', $output);
    }

    #[Test]
    public function registerModuleValidatesNick(): void
    {
        global $submit, $nick, $email, $pw_new, $pw_new2, $user_details;
        $submit = 1;
        $nick = '';
        $email = 'test@test.com';
        $pw_new = 'pass';
        $pw_new2 = 'pass';
        $user_details = null;

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('Bitte geben Sie einen Benutzernamen ein.', $output);
    }

    #[Test]
    public function registerModuleValidatesEmail(): void
    {
        global $submit, $nick, $email, $pw_new, $pw_new2, $user_details;
        $submit = 1;
        $nick = 'TestUser';
        $_POST['nick'] = 'TestUser';
        $email = '';
        $pw_new = 'pass';
        $pw_new2 = 'pass';
        $user_details = null;

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('E-Mail-Adresse', $output);
    }

    #[Test]
    public function registerModuleValidatesPasswordMismatch(): void
    {
        global $submit, $nick, $email, $pw_new, $pw_new2, $user_details;
        $submit = 1;
        $nick = 'TestUser';
        $email = 'test@test.com';
        $pw_new = 'pass1';
        $pw_new2 = 'pass2';
        $user_details = null;

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('Passwort', $output);
    }

    #[Test]
    public function registerModuleValidatesEmptyPassword(): void
    {
        global $submit, $nick, $email, $pw_new, $pw_new2, $user_details;
        $submit = 1;
        $nick = 'TestUser';
        $email = 'test@test.com';
        $pw_new = '';
        $pw_new2 = '';
        $user_details = null;

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('Passwort', $output);
    }

    #[Test]
    public function registerModuleDuplicateNick(): void
    {
        global $submit, $nick, $email, $pw_new, $pw_new2, $db_handler, $user_details;
        $submit = 1;
        $nick = 'ExistingUser';
        $_POST['nick'] = 'ExistingUser';
        $email = 'new@test.com';
        $pw_new = 'pass1234';
        $pw_new2 = 'pass1234';
        $user_details = null;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // DELETE abgelaufene iplock-Einträge
        $db_handler->addResult([['c' => 0]]); // rate-limit counter
        $db_handler->addResult([['user_id' => 7]]); // nick exists

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('Dieser Benutzername ist bereits vergeben.', $output);
        $this->assertSame([], $GLOBALS['pdl_test_mails']);
    }

    #[Test]
    public function registerModuleDuplicateEmail(): void
    {
        global $submit, $nick, $email, $pw_new, $pw_new2, $db_handler, $user_details;
        $submit = 1;
        $nick = 'NewUser';
        $_POST['nick'] = 'NewUser';
        $email = 'existing@test.com';
        $pw_new = 'pass1234';
        $pw_new2 = 'pass1234';
        $user_details = null;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // DELETE abgelaufene iplock-Einträge
        $db_handler->addResult([['c' => 0]]); // rate-limit counter
        $db_handler->addResult([]); // nick check (no match)
        $db_handler->addResult([['email' => 'existing@test.com']]); // email exists

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('E-Mail-Adresse', $output);
        $this->assertStringContainsString('bereits', $output);
    }

    #[Test]
    public function registerModuleSuccess(): void
    {
        global $submit, $nick, $email, $pw_new, $pw_new2, $db_handler, $user_details, $homepage, $icq, $get_letter, $template;
        $template['mail_register'] = ''; // leere Vorlage: eingebauter Standardtext
        $submit = 1;
        $nick = 'NewUser';
        $_POST['nick'] = 'NewUser';
        $email = 'newuser@test.com';
        $pw_new = 'secure123';
        $pw_new2 = 'secure123';
        $homepage = 'www.example.com';
        $icq = 0;
        $get_letter = 'Y';
        $user_details = null;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // DELETE abgelaufene iplock-Einträge
        $db_handler->addResult([['c' => 0]]); // rate-limit counter
        $db_handler->addResult([]); // nick check
        $db_handler->addResult([]); // email check
        $db_handler->addResult([]); // insert

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('erfolgreich', $output);
        // P13: nach der Registrierung nur „Jetzt anmelden“, kein „Zum Profil“
        $this->assertStringContainsString('Jetzt anmelden', $output);
        $this->assertStringNotContainsString('Zum Profil', $output);
        // P01/P03: Mail mit Text, absolutem Link und kodiertem Betreff
        $this->assertCount(1, $GLOBALS['pdl_test_mails']);
        $mail = $GLOBALS['pdl_test_mails'][0];
        $this->assertSame('newuser@test.com', $mail['to']);
        $this->assertStringContainsString('Hallo NewUser,', $mail['body']);
        $this->assertMatchesRegularExpression('#https?://[^/\s]+/\S*downloads\.php\?usercenter=login#', $mail['body']);
        $this->assertSame('text/plain; charset=UTF-8', $mail['headers']['Content-Type']);
    }

    // ==================== pdl_uprofil.modul.php ====================

    #[Test]
    public function profilModuleShowsLoginRequired(): void
    {
        global $user_details, $submit;
        $user_details = null;
        $submit = 0;

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('melden Sie sich an', $output);
    }

    #[Test]
    public function profilModuleShowsForm(): void
    {
        global $user_details, $submit;
        $user_details = [
            'user_id' => 1, 'nick' => 'Test', 'email' => 'test@test.com',
            'homepage' => 'https://test.com', 'icq' => 12345, 'get_letter' => 'Y',
            'passwort' => password_hash('oldpass', PASSWORD_DEFAULT),
        ];
        $submit = 0;

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('test@test.com', $output);
        $this->assertStringContainsString('checked', $output);
    }

    #[Test]
    public function profilModuleWrongPassword(): void
    {
        global $user_details, $submit, $pw_old, $pw_new, $pw_new2, $email, $homepage, $icq, $get_letter;
        $user_details = [
            'user_id' => 1, 'nick' => 'Test', 'email' => 'test@test.com',
            'homepage' => '', 'icq' => 0, 'get_letter' => 'N',
            'passwort' => password_hash('correct', PASSWORD_DEFAULT),
        ];
        $submit = 1;
        $pw_old = 'wrong';
        $pw_new = '';
        $pw_new2 = '';
        $email = 'test@test.com';
        $homepage = '';
        $icq = 0;
        $get_letter = 'N';

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('falsch', $output);
    }

    #[Test]
    public function profilModuleUpdateWithoutPasswordChange(): void
    {
        global $user_details, $submit, $pw_old, $pw_new, $pw_new2, $email, $homepage, $icq, $get_letter, $db_handler;
        $user_details = [
            'user_id' => 1, 'nick' => 'Test', 'email' => 'test@test.com',
            'homepage' => '', 'icq' => 0, 'get_letter' => 'N',
            'passwort' => password_hash('correct', PASSWORD_DEFAULT),
        ];
        $submit = 1;
        $pw_old = 'correct';
        $pw_new = '';
        $pw_new2 = '';
        $email = 'new@test.com';
        $homepage = 'example.com';
        $icq = 0;
        $get_letter = 'N';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('Ihr Profil wurde gespeichert.', $output);
    }

    #[Test]
    public function profilModulePasswordMismatch(): void
    {
        global $user_details, $submit, $pw_old, $pw_new, $pw_new2, $email, $homepage, $icq, $get_letter;
        $user_details = [
            'user_id' => 1, 'nick' => 'Test', 'email' => 'test@test.com',
            'homepage' => '', 'icq' => 0, 'get_letter' => 'N',
            'passwort' => password_hash('correct', PASSWORD_DEFAULT),
        ];
        $submit = 1;
        $pw_old = 'correct';
        $pw_new = 'new1';
        $pw_new2 = 'new2';
        $email = 'test@test.com';
        $homepage = '';
        $icq = 0;
        $get_letter = 'N';

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('stimmen nicht überein', $output);
    }

    #[Test]
    public function profilModuleChangePassword(): void
    {
        global $user_details, $submit, $pw_old, $pw_new, $pw_new2, $email, $homepage, $icq, $get_letter, $db_handler;
        $user_details = [
            'user_id' => 1, 'nick' => 'Test', 'email' => 'test@test.com',
            'homepage' => '', 'icq' => 0, 'get_letter' => 'N',
            'passwort' => password_hash('correct', PASSWORD_DEFAULT),
        ];
        $submit = 1;
        $pw_old = 'correct';
        $pw_new = 'newpass123';
        $pw_new2 = 'newpass123';
        $email = 'test@test.com';
        $homepage = '';
        $icq = 0;
        $get_letter = 'Y';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('Ihr Profil wurde gespeichert.', $output);
    }

    #[Test]
    public function profilModuleLegacyMd5Password(): void
    {
        global $user_details, $submit, $pw_old, $pw_new, $pw_new2, $email, $homepage, $icq, $get_letter, $db_handler;
        $user_details = [
            'user_id' => 1, 'nick' => 'Test', 'email' => 'test@test.com',
            'homepage' => '', 'icq' => 0, 'get_letter' => 'N',
            'passwort' => md5('oldpass'),
        ];
        $submit = 1;
        $pw_old = 'oldpass';
        $pw_new = '';
        $pw_new2 = '';
        $email = 'test@test.com';
        $homepage = '';
        $icq = 0;
        $get_letter = 'N';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('Ihr Profil wurde gespeichert.', $output);
    }

    #[Test]
    public function profilModuleWithHomepagePrefix(): void
    {
        global $user_details, $submit, $pw_old, $pw_new, $pw_new2, $email, $homepage, $icq, $get_letter, $db_handler;
        $user_details = [
            'user_id' => 1, 'nick' => 'Test', 'email' => 'test@test.com',
            'homepage' => '', 'icq' => 0, 'get_letter' => 'N',
            'passwort' => password_hash('correct', PASSWORD_DEFAULT),
        ];
        $submit = 1;
        $pw_old = 'correct';
        $pw_new = 'newpw123';
        $pw_new2 = 'newpw123';
        $email = 'test@test.com';
        $homepage = 'www.example.com'; // no http prefix
        $icq = 555;
        $get_letter = 'Y';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('Ihr Profil wurde gespeichert.', $output);
    }

    // ==================== pdl_ucomments.modul.php ====================

    #[Test]
    public function commentsModuleNoRights(): void
    {
        // Angemeldet, aber die Benutzergruppe darf nicht kommentieren (P26)
        global $user_rights, $settings, $user_details;
        $settings['enable_comments'] = 'Y';
        $user_rights['addcomments'] = 'N';
        $user_details = ['nick' => 'TestUser', 'user_id' => 1];

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('Ihre Benutzergruppe darf keine Kommentare schreiben.', $output);
        $this->assertStringNotContainsString('pdlCommentForm', $output);
    }

    #[Test]
    public function commentsModuleCommentsDisabled(): void
    {
        global $user_rights, $settings;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('Kommentare sind auf dieser Seite ausgeschaltet.', $output);
    }

    #[Test]
    public function commentsModuleShowsFormLoggedIn(): void
    {
        global $user_rights, $settings, $submit, $user_details, $release_id, $db_handler;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'Y';
        $submit = 0;
        $release_id = 1;
        $user_details = ['nick' => 'TestUser', 'user_id' => 1];
        $db_handler = new MockDbHandler();
        $db_handler->addResult([['release_id' => 1, 'name' => 'Rel']]); // Release-Prüfung

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('TestUser', $output);
        $this->assertStringContainsString('id="pdlCommentForm"', $output);
        $this->assertStringContainsString('name="csrf_token"', $output);
        $this->assertStringNotContainsString('bgcolor', $output);
    }

    #[Test]
    public function commentsModuleShowsLoginPromptForGuest(): void
    {
        // P26: Gäste bekommen nur den Hinweis mit Anmelde- und Registrierungslink
        global $user_rights, $settings, $submit, $user_details, $release_id;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'Y';
        $submit = 0;
        $release_id = 1;
        $user_details = null;

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('melden Sie sich an', $output);
        $this->assertStringContainsString('usercenter=register', $output);
        $this->assertStringContainsString('Kommentar zu schreiben', $output);
    }

    #[Test]
    public function commentsModuleRejectsHiddenOrUnknownRelease(): void
    {
        global $user_rights, $settings, $submit, $user_details, $release_id, $db_handler;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'Y';
        $submit = 0;
        $release_id = 99;
        $user_details = ['nick' => 'TestUser', 'user_id' => 1];
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // Release nicht gefunden oder versteckt

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('nicht öffentlich', $output);
    }

    #[Test]
    public function commentsModuleSubmitEmpty(): void
    {
        global $user_rights, $settings, $submit, $user_details, $release_id, $titel, $text, $db_handler;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'Y';
        $submit = 1;
        $release_id = 1;
        $titel = '';
        $text = '';
        $user_details = ['nick' => 'TestUser', 'user_id' => 1];
        $db_handler = new MockDbHandler();
        $db_handler->addResult([['release_id' => 1, 'name' => 'Rel']]);

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('Titel und Text', $output);
        $this->assertStringContainsString('Bitte prüfen Sie Ihre Eingaben', $output);
    }

    #[Test]
    public function commentsModuleSubmitSuccess(): void
    {
        global $user_rights, $settings, $submit, $user_details, $release_id, $titel, $text, $db_handler;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'Y';
        $submit = 1;
        $release_id = 1;
        $titel = 'Test Title';
        $text = 'Test Content';
        $user_details = ['nick' => 'TestUser', 'user_id' => 1];
        $db_handler = new MockDbHandler();
        $db_handler->addResult([['release_id' => 1, 'name' => 'Rel']]); // Release-Prüfung
        $db_handler->addResult([]); // keine Sperre (1 Minute)
        $db_handler->addResult([]); // INSERT Kommentar

        unset($_SESSION['pdl_comment_flash']);
        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('<div id="pdlCommentSaved">', $output);
        $this->assertStringContainsString('Ihr Kommentar wurde veröffentlicht.', $output);
        // Ohne Vorabverarbeitung im Header (Tests): Link zum Release mit Rückmeldung
        $this->assertStringContainsString('href="downloads.php?release_id=1&amp;commented=1#pdlComments"', $output);
        $this->assertSame(['release_id' => 1, 'comment_id' => 0], $_SESSION['pdl_comment_flash'] ?? null);
        unset($_SESSION['pdl_comment_flash']);
    }

    #[Test]
    public function commentsModuleSubmitIsRateLimited(): void
    {
        global $user_rights, $settings, $submit, $user_details, $release_id, $titel, $text, $db_handler;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'Y';
        $submit = 1;
        $release_id = 1;
        $titel = 'Noch einer';
        $text = 'Text';
        $user_details = ['nick' => 'TestUser', 'user_id' => 1];
        $db_handler = new MockDbHandler();
        $db_handler->addResult([['release_id' => 1, 'name' => 'Rel']]);
        $db_handler->addResult([['file_id' => 1]]); // Kommentar vor weniger als einer Minute

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('Bitte warten Sie eine Minute', $output);
        // Eingaben bleiben im Formular stehen
        $this->assertStringContainsString('value="Noch einer"', $output);
    }

    // ==================== pdl_showscreen.modul.php ====================

    #[Test]
    public function showscreenModuleNotFound(): void
    {
        global $db_handler, $screen_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // select
        $screen_id = 999;

        $output = $this->includeModule('pdl_showscreen.modul.php');
        $this->assertStringContainsString('Screenshot nicht gefunden', $output);
    }

    #[Test]
    public function showscreenModuleDisplaysScreen(): void
    {
        global $db_handler, $screen_id;
        $_SESSION['pdl_viewed_screen'] = [];
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['screen_id' => 1, 'release_id' => 5, 'views' => 42, 'text' => 'Screenshot text',
             'release_name' => 'ScreenRel', 'released' => 'Y'],
        ]);
        $db_handler->addResult([]); // views update
        $screen_id = 1;

        $output = $this->includeModule('pdl_showscreen.modul.php');
        $this->assertStringContainsString('Screenshot text', $output);
        $this->assertStringContainsString('<figcaption', $output);
        $this->assertStringContainsString('Aufrufe: 43', $output);
        $this->assertStringContainsString('Zurück zum Release', $output);
        $this->assertStringContainsString('release_id=5', $output);
        // Ohne Bilddatei kein kaputtes Bildsymbol, sondern ein Hinweis
        $this->assertStringContainsString('Die Bilddatei zu diesem Screenshot fehlt.', $output);
        $this->assertStringNotContainsString('<img', $output);
    }

    #[Test]
    public function showscreenModuleHidesScreensOfHiddenReleases(): void
    {
        global $db_handler, $screen_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['screen_id' => 2, 'release_id' => 3, 'views' => 0, 'text' => 'Geheim', 'release_name' => 'Versteckt', 'released' => 'N'],
        ]);
        $screen_id = 2;

        $output = $this->includeModule('pdl_showscreen.modul.php');
        $this->assertStringContainsString('Screenshot nicht gefunden', $output);
        $this->assertStringNotContainsString('Geheim', $output);
    }

    // ==================== pdl_ulost.modul.php ====================

    #[Test]
    public function lostModuleShowsForm(): void
    {
        global $submit;
        $submit = 0;

        $output = $this->includeModule('pdl_ulost.modul.php');
        $this->assertStringContainsString('name="email"', $output);
        $this->assertStringContainsString('Passwort vergessen', $output);
    }

    #[Test]
    public function lostModuleUserNotFoundReturnsGenericMessage(): void
    {
        // Schutz vor User-Enumeration: Modul liefert immer dieselbe generische
        // Bestätigungsmeldung, egal ob das Konto existiert oder nicht.
        global $submit, $email, $db_handler;
        $submit = 1;
        $email = 'notfound@test.com';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeModule('pdl_ulost.modul.php');
        $this->assertStringContainsString('Benutzerkonto existiert', $output);
        $this->assertStringContainsString('60 Minuten', $output);
        $this->assertSame([], $GLOBALS['pdl_test_mails']);
    }

    #[Test]
    public function lostModuleUserFoundReturnsGenericMessage(): void
    {
        // Auch wenn der Account existiert, wird dieselbe generische Meldung
        // ausgegeben (User-Enumeration-Schutz).
        global $submit, $email, $db_handler;
        $submit = 1;
        $email = 'found@test.com';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // DELETE abgelaufene iplock-Einträge
        $db_handler->addResult([['c' => 0]]); // rate-limit counter
        $db_handler->addResult([]); // INSERT iplock
        $db_handler->addResult([
            ['user_id' => 1, 'nick' => 'TestUser', 'email' => 'found@test.com'],
        ]);
        $db_handler->addResult([]); // update remind_code

        $output = $this->includeModule('pdl_ulost.modul.php');
        $this->assertStringContainsString('Benutzerkonto existiert', $output);
        // P01/P02/P14: Mail mit absolutem Reset-Link und richtiger Gültigkeit
        $this->assertCount(1, $GLOBALS['pdl_test_mails']);
        $mail = $GLOBALS['pdl_test_mails'][0];
        $this->assertSame('found@test.com', $mail['to']);
        // Angepasste Vorlage mit alten Platzhaltern {user}/{url} aus 3.5.0
        $this->assertMatchesRegularExpression('#^Dear TestUser, https?://\S+downloads\.php\?usercenter=lost2&remind_code=[0-9a-f]{32}$#', $mail['body']);
        $this->assertStringStartsWith('=?UTF-8?B?', $mail['subject']);
    }

    // ==================== pdl_ulost2.modul.php ====================

    #[Test]
    public function lost2ModuleInvalidCode(): void
    {
        global $remind_code, $db_handler;
        $remind_code = 'invalid';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeModule('pdl_ulost2.modul.php');
        $this->assertStringContainsString('Der Link ist ungültig oder abgelaufen.', $output);
    }

    #[Test]
    public function lost2ModuleValidCodeRendersResetForm(): void
    {
        global $remind_code, $db_handler, $submit;
        $submit = 0;
        $remind_code = '0123456789abcdef0123456789abcdef';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['user_id' => 1, 'nick' => 'TestUser', 'email' => 'test@test.com'],
        ]);

        $output = $this->includeModule('pdl_ulost2.modul.php');
        // Beim ersten Aufruf (kein Submit) wird das Formular zum Setzen eines
        // neuen Passworts mit CSRF-Token gerendert.
        $this->assertStringContainsString('Neues Passwort', $output);
        $this->assertStringContainsString('csrf_token', $output);
    }

    // ==================== pdl_stats.inc.php ====================

    #[Test]
    public function statsWidgetShowsStatistics(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['file_id' => 1, 'size' => 1024, 'downloads' => 10, 'mirror' => 0],
            ['file_id' => 2, 'size' => 2048, 'downloads' => 5, 'mirror' => 0],
        ]);

        $output = $this->includeModule('pdl_stats.inc.php');
        $this->assertStringContainsString('2', $output);
    }

    #[Test]
    public function statsWidgetWithMirrors(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        // Files query with a mirror
        $db_handler->addResult([
            ['file_id' => 1, 'size' => 1024, 'downloads' => 10, 'mirror' => 0],
            ['file_id' => 2, 'size' => 0, 'downloads' => 5, 'mirror' => 1],
        ]);
        // Mirror lookup for file 2
        $db_handler->addResult([
            ['file_id' => 1, 'size' => 1024, 'downloads' => 10, 'mirror' => 0],
        ]);

        $output = $this->includeModule('pdl_stats.inc.php');
        $this->assertNotEmpty($output);
    }

    // ==================== pdl_ordner.modul.php ====================

    #[Test]
    public function ordnerModuleEmptyFolder(): void
    {
        global $db_handler, $ordner_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);
        $db_handler->addResult([]);
        $ordner_id = 0;

        $output = $this->includeModule('pdl_ordner.modul.php');
        $this->assertStringContainsString('leer', $output);
    }

    #[Test]
    public function ordnerModuleWithSubfolders(): void
    {
        global $db_handler, $ordner_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // files_check
        $db_handler->addResult([['ordner_id' => 1]]); // ordner_check
        $db_handler->addResult([
            ['ordner_id' => 1, 'name' => 'SubFolder', 'text' => 'Desc'],
        ]);
        $db_handler->addResult([]); // sub: releases
        $db_handler->addResult([]); // sub: subdirs
        $ordner_id = 0;

        $output = $this->includeModule('pdl_ordner.modul.php');
        $this->assertStringContainsString('SubFolder', $output);
    }

    #[Test]
    public function ordnerModuleWithReleases(): void
    {
        global $db_handler, $ordner_id, $page;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([['release_id' => 1]]); // files_check (nur veröffentlichte)
        $db_handler->addResult([]); // ordner_check
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'TestRelease', 'text' => '', 'ordner_id' => 0,
             'votes' => 0, 'voted' => 0, 'views' => 10, 'downloads' => 5, 'time' => time(), 'uploader' => 0],
        ]);
        $db_handler->addResult([['tsize' => 1536, 'cnt' => 1]]); // Größe und Anzahl Dateien
        $ordner_id = 0;
        $page = 1;
        global $template;
        $template['release_row'] = '';
        $template['release_box'] = '';

        $output = $this->includeModule('pdl_ordner.modul.php');
        $this->assertStringContainsString('TestRelease', $output);
        $this->assertStringContainsString('1 Datei &middot; 1,5 KB', $output);
        // Kein leeres Sortierformular mehr um die Liste
        $this->assertStringNotContainsString('change_list=1', $output);
    }

    #[Test]
    public function ordnerModuleWithTextTruncation(): void
    {
        global $db_handler, $ordner_id, $page, $settings;
        $settings['trenn_durch'] = 'zeichen';
        $settings['trenn_zeichen'] = 10;
        $settings['trenn_string'] = '';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([['release_id' => 1]]); // files_check
        $db_handler->addResult([]); // ordner_check
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'TestRelease', 'text' => 'This is a very long description that should be truncated',
             'ordner_id' => 0, 'votes' => 0, 'voted' => 0, 'views' => 10, 'downloads' => 5, 'time' => time(), 'uploader' => 0],
        ]);
        $db_handler->addResult([['tsize' => 2048, 'cnt' => 1]]); // size
        $ordner_id = 0;
        $page = 1;

        $output = $this->includeModule('pdl_ordner.modul.php');
        // Gekürzt an der Wortgrenze, mit Auslassungszeichen
        $this->assertStringContainsString('This is a…', $output);
    }

    #[Test]
    public function ordnerModuleTruncatesUmlautsSafely(): void
    {
        global $db_handler, $ordner_id, $page, $settings;
        $settings['trenn_durch'] = 'zeichen';
        $settings['trenn_zeichen'] = 15;
        $settings['trenn_string'] = '';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([['release_id' => 1]]);
        $db_handler->addResult([]);
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'Umlaute', 'text' => 'Größenprüfung über [b]Äußerungen[/b] und mehr',
             'ordner_id' => 0, 'votes' => 0, 'voted' => 0, 'views' => 0, 'downloads' => 0, 'time' => time(), 'uploader' => 0],
        ]);
        $db_handler->addResult([['tsize' => 0, 'cnt' => 0]]);
        $ordner_id = 0;
        $page = 1;

        $output = $this->includeModule('pdl_ordner.modul.php');
        $this->assertTrue(mb_check_encoding($output, 'UTF-8'));
        $this->assertStringContainsString('Größenprüfung…', $output);
        $this->assertStringNotContainsString('[b]', $output);
    }

    #[Test]
    public function ordnerModuleUnknownFolderShowsNotFound(): void
    {
        global $db_handler, $ordner_id, $page, $pdl_current_ordner;
        $pdl_current_ordner = null;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // Ordner existiert nicht
        $ordner_id = 999;
        $page = 1;

        $output = $this->includeModule('pdl_ordner.modul.php');
        $this->assertStringContainsString('Ordner nicht gefunden', $output);
        $this->assertStringNotContainsString('noch leer', $output);
        $ordner_id = 0;
    }

    #[Test]
    public function ordnerModuleWithStringSeparator(): void
    {
        global $db_handler, $ordner_id, $page, $settings;
        $settings['trenn_durch'] = 'string';
        $settings['trenn_string'] = '---';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([['release_id' => 1]]); // files_check
        $db_handler->addResult([]); // ordner_check
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'Test', 'text' => 'Short text---Rest hidden',
             'ordner_id' => 0, 'votes' => 0, 'voted' => 0, 'views' => 10, 'downloads' => 5, 'time' => time(), 'uploader' => 0],
        ]);
        $db_handler->addResult([['tsize' => 512, 'cnt' => 1]]); // size
        $ordner_id = 0;
        $page = 1;

        $output = $this->includeModule('pdl_ordner.modul.php');
        $this->assertStringContainsString('Short text', $output);
    }

    // ==================== pdl_search.modul.php ====================

    #[Test]
    public function searchModuleDisabled(): void
    {
        global $settings;
        $settings['enable_search'] = 'N';

        $output = $this->includeModule('pdl_search.modul.php');
        $this->assertStringContainsString('deaktiviert', $output);
    }

    #[Test]
    public function searchModuleShowsForm(): void
    {
        global $settings, $submit;
        $settings['enable_search'] = 'Y';
        $submit = 0;

        $output = $this->includeModule('pdl_search.modul.php');
        $this->assertStringContainsString('Suche', $output);
        $this->assertStringContainsString('Suchbegriff', $output);
    }

    #[Test]
    public function searchModuleNoResults(): void
    {
        global $settings, $submit, $text, $in, $db_handler, $page;
        $settings['enable_search'] = 'Y';
        $submit = 1;
        $text = 'nonexistent';
        $in = 'texttitel';
        $page = 1;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeModule('pdl_search.modul.php');
        $this->assertStringContainsString('0', $output);
        $this->assertStringContainsString('Treffer', $output);
    }

    #[Test]
    public function searchModuleWithResults(): void
    {
        global $settings, $submit, $text, $in, $db_handler, $page;
        $settings['enable_search'] = 'Y';
        $submit = 1;
        $text = 'test';
        $in = 'titel';
        $page = 1;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([['c' => 1]]); // COUNT(*)
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'TestApp', 'text' => 'Desc', 'ordner_id' => 0,
             'votes' => 0, 'voted' => 0, 'views' => 0, 'downloads' => 0, 'time' => time(), 'uploader' => 0],
        ]);
        $db_handler->addResult([['tsize' => 2048, 'cnt' => 1]]);

        $output = $this->includeModule('pdl_search.modul.php');
        $this->assertStringContainsString('ergab <strong>1</strong> Treffer', $output);
        $this->assertStringContainsString('TestApp', $output);
        // Formular bleibt über den Ergebnissen, vorbefüllt
        $this->assertStringContainsString('value="test"', $output);
        $this->assertStringContainsString('<option value="titel" selected>', $output);
    }

    #[Test]
    public function searchModuleSearchInText(): void
    {
        global $settings, $submit, $text, $in, $db_handler, $page;
        $settings['enable_search'] = 'Y';
        $submit = 1;
        $text = 'query';
        $in = 'text';
        $page = 1;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeModule('pdl_search.modul.php');
        $this->assertStringContainsString('0', $output);
    }

    // ==================== pdl_release.modul.php ====================

    /**
     * Release-Datensatz für die Tests.
     */
    private function releaseRow(array $overrides = []): array
    {
        return array_merge([
            'release_id' => 1, 'name' => 'TestRelease', 'text' => 'Description', 'released' => 'Y',
            'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 100,
            'time' => 1790000000, 'uploader' => 0, 'autor' => -1, 'autor_email' => '', 'autor_nick' => '',
            'autor_homepage' => '',
        ], $overrides);
    }

    /**
     * Bootstrap-Ansicht erzwingen (keine eigenen Vorlagen) und Sitzungswerte zurücksetzen.
     */
    private function useBootstrapReleaseView(): void
    {
        global $template;
        $template['file_detail'] = '';
        $template['dfiles_row'] = '';
        $template['comments'] = '';
        unset($_SESSION['pdl_viewed'], $_SESSION['pdl_vote_flash']);
        unset($_GET['voted']);
    }

    #[Test]
    public function releaseModuleNotFound(): void
    {
        global $db_handler, $release_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // release query
        $release_id = 999;

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('Release nicht gefunden', $output);
    }

    #[Test]
    public function releaseModuleHiddenRelease(): void
    {
        global $db_handler, $release_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow(['name' => 'GeheimName', 'released' => 'N'])]);
        $release_id = 1;

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('Dieses Release ist nicht öffentlich.', $output);
        $this->assertStringNotContainsString('GeheimName', $output);
    }

    #[Test]
    public function releaseModuleDisplaysRelease(): void
    {
        // Eigene Vorlage file_detail (Platzhalter) wird weiterhin unterstützt
        global $db_handler, $release_id, $user_rights, $settings;
        unset($_SESSION['pdl_viewed']);
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([ // files
            ['file_id' => 1, 'url' => 'files/test.zip', 'size' => 1024, 'downloads' => 10, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // iplock (Bewertung)
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_rights['vote'] = 'Y';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('TestRelease', $output);
        $this->assertStringContainsString('Unbekannt', $output);
    }

    #[Test]
    public function releaseModuleCountsViewsOncePerSession(): void
    {
        global $db_handler, $release_id, $user_rights, $settings;
        $this->useBootstrapReleaseView();
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';
        $release_id = 1;

        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([]); // files
        $db_handler->addResult([]); // screens
        $first = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('<dt class="mb-0">Aufrufe:</dt><dd class="mb-0">101</dd>', $first);
        $this->assertSame(4, $db_handler->querys);

        // Neuladen in derselben Sitzung: kein UPDATE mehr
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // files
        $db_handler->addResult([]); // screens
        $second = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('<dt class="mb-0">Aufrufe:</dt><dd class="mb-0">100</dd>', $second);
        $this->assertSame(3, $db_handler->querys);
    }

    #[Test]
    public function releaseModuleWithAutorUser(): void
    {
        global $db_handler, $release_id, $user_rights, $settings, $users;
        $this->useBootstrapReleaseView();
        $users[5] = ['nick' => 'AuthorUser', 'email' => 'author@test.com', 'homepage' => ''];
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow(['autor' => 5])]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([]); // files
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('AuthorUser', $output);
        $this->assertStringNotContainsString('mailto:', $output);
    }

    #[Test]
    public function releaseModuleWithExternalAutor(): void
    {
        // P41: Name und Homepage, aber keine E-Mail-Adresse als mailto-Link
        global $db_handler, $release_id, $user_rights, $settings;
        $this->useBootstrapReleaseView();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow(['autor' => 0, 'autor_email' => 'ext@test.com',
            'autor_nick' => 'ExtAuthor', 'autor_homepage' => 'https://ext.com'])]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([]); // files
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('ExtAuthor', $output);
        $this->assertStringContainsString('href="https://ext.com"', $output);
        $this->assertStringNotContainsString('mailto:', $output);
        $this->assertStringNotContainsString('ext@test.com', $output);
    }

    #[Test]
    public function releaseModuleExternalAutorNoEmail(): void
    {
        global $db_handler, $release_id, $user_rights, $settings;
        $this->useBootstrapReleaseView();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow(['autor' => 0, 'autor_nick' => 'JustNick'])]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([]); // files
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('JustNick', $output);
    }

    #[Test]
    public function releaseModuleFileLinksUseLoadFileAndGroupMirrors(): void
    {
        // P07/P09: alle Dateilinks über load_file, Spiegel als "Alternativ: Spiegel-Server"
        global $db_handler, $release_id, $user_rights, $settings;
        $this->useBootstrapReleaseView();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([ // files: Hauptdatei und Spiegel
            ['file_id' => 1, 'url' => 'pdl-files/1/t.zip', 'size' => 1536, 'downloads' => 10, 'mirror' => 0, 'release_id' => 1, 'name' => 'Programm'],
            ['file_id' => 2, 'url' => 'https://mirror.example/t.zip', 'size' => 0, 'downloads' => 5, 'mirror' => 1, 'release_id' => 1, 'name' => 'Spiegel A'],
        ]);
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('href="downloads.php?load_file=1"', $output);
        $this->assertStringContainsString('href="downloads.php?load_file=2"', $output);
        $this->assertStringContainsString('class="btn btn-primary btn-sm pdl-download-btn"', $output);
        $this->assertStringContainsString('Alternativ: <a class="pdl-mirror-link"', $output);
        $this->assertStringContainsString('>Spiegel-Server</a>', $output);
        $this->assertStringNotContainsString('pdl-files/1/t.zip', $output);
        $this->assertStringNotContainsString('mirror.example', $output);
        // Eckdaten: 1 Datei, Downloads inkl. Spiegel, Größe ohne Spiegel
        $this->assertStringContainsString('<dt class="mb-0">Anzahl Dateien:</dt><dd class="mb-0">1</dd>', $output);
        $this->assertStringContainsString('<dt class="mb-0">Downloads:</dt><dd class="mb-0">15</dd>', $output);
        $this->assertStringContainsString('<dt class="mb-0">Gesamtgröße:</dt><dd class="mb-0">1,5 KB</dd>', $output);
        $this->assertStringContainsString('1,5 KB &middot; 10 Downloads', $output);
    }

    #[Test]
    public function releaseModuleCustomFileTemplateStillUsesLoadFile(): void
    {
        global $db_handler, $release_id, $user_rights, $settings, $template;
        $this->useBootstrapReleaseView();
        $template['dfiles_row'] = '<a href="{url}">{filename}</a>';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([
            ['file_id' => 7, 'url' => 'pdl-files/1/t.zip', 'size' => 10, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('<a href="downloads.php?load_file=7">t.zip</a>', $output);
    }

    #[Test]
    public function releaseModuleWithMirrorOfOtherRelease(): void
    {
        global $db_handler, $release_id, $user_rights, $settings;
        $this->useBootstrapReleaseView();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([
            ['file_id' => 3, 'url' => 'https://m.example/x.zip', 'size' => 0, 'downloads' => 2, 'mirror' => 9, 'release_id' => 1, 'name' => 'Fremder Spiegel'],
        ]);
        $db_handler->addResult([['size' => 2048]]); // Größe des Originals
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('Fremder Spiegel', $output);
        $this->assertStringContainsString('badge text-bg-secondary ms-1">Spiegel-Server', $output);
        $this->assertStringContainsString('2 KB &middot; 2 Downloads', $output);
    }

    #[Test]
    public function releaseModuleVoteFormIsSeparateAndHasCsrf(): void
    {
        // P04: eigenes kleines Formular mit CSRF-Token, nicht um die ganze Karte
        global $db_handler, $release_id, $user_rights, $settings, $user_details;
        $this->useBootstrapReleaseView();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'pdl-files/1/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // iplock: noch nicht bewertet
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_details = ['nick' => 'Voter', 'user_id' => 2];
        $user_rights['vote'] = 'Y';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('id="pdlVoteForm"', $output);
        $this->assertStringContainsString('name="csrf_token"', $output);
        $this->assertStringContainsString('name="vote" value="1"', $output);
        $this->assertStringContainsString('<select id="pdlVoteId" name="vote_id"', $output);
        $this->assertStringContainsString('id="pdlVoteSubmit">Bewerten</button>', $output);
        $this->assertStringContainsString('Noch keine Bewertungen.', $output);
        $this->assertSame(1, substr_count($output, '<form'));
        $this->assertGreaterThan(strpos($output, 'id="pdlFileList"'), strpos($output, '<form'));
    }

    #[Test]
    public function releaseModuleShowsThanksAfterVoteRedirect(): void
    {
        global $db_handler, $release_id, $user_rights, $settings, $user_details;
        $this->useBootstrapReleaseView();
        $_SESSION['pdl_vote_flash'] = ['release_id' => 1, 'status' => 'ok', 'vote' => 8];
        $_GET['voted'] = '1';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow(['votes' => 1, 'voted' => 8])]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([]); // files
        $db_handler->addResult([['file_id' => 1]]); // iplock: bereits bewertet
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_details = ['nick' => 'Voter', 'user_id' => 2];
        $user_rights['vote'] = 'Y';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        unset($_GET['voted']);
        $this->assertStringContainsString('Danke für Ihre Bewertung (8/10).', $output);
        $this->assertStringContainsString('Durchschnitt: <strong>8</strong> von 10 (1 Stimme)', $output);
        $this->assertStringNotContainsString('id="pdlVoteForm"', $output);
        $this->assertArrayNotHasKey('pdl_vote_flash', $_SESSION);
    }

    #[Test]
    public function releaseModuleVoteAlreadyLocked(): void
    {
        global $db_handler, $release_id, $user_rights, $settings, $user_details;
        $this->useBootstrapReleaseView();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow(['votes' => 2, 'voted' => 15])]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([]); // files
        $db_handler->addResult([['file_id' => 1]]); // iplock: bereits bewertet
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_details = ['nick' => 'Voter', 'user_id' => 2];
        $user_rights['vote'] = 'Y';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('Sie haben dieses Release bereits bewertet.', $output);
        $this->assertStringContainsString('Durchschnitt: <strong>7,5</strong> von 10 (2 Stimmen)', $output);
        $this->assertStringNotContainsString('id="pdlVoteForm"', $output);
    }

    #[Test]
    public function releaseModuleGuestWithoutVoteRightSeesLoginHint(): void
    {
        global $db_handler, $release_id, $user_rights, $settings, $user_details;
        $this->useBootstrapReleaseView();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([]); // files
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_details = null;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('melden Sie sich an</a>, um dieses Release zu bewerten.', $output);
        $this->assertStringNotContainsString('Vote!', $output);
    }

    #[Test]
    public function releaseModuleWithCommentsEnabled(): void
    {
        global $db_handler, $release_id, $user_rights, $settings, $user_details;
        $this->useBootstrapReleaseView();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow(['name' => 'Commented'])]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([]); // files
        $db_handler->addResult([]); // screens
        $db_handler->addResult([]); // comments
        $release_id = 1;
        $user_details = ['nick' => 'User', 'user_id' => 1];
        $user_rights['vote'] = 'N';
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'Y';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('Noch keine Kommentare.', $output);
        $this->assertSame(1, substr_count($output, 'Kommentar schreiben'));
        $this->assertStringContainsString('id="pdlCommentForm"', $output);
        $this->assertStringContainsString('id="pdlCommentHelp"', $output);
        $this->assertStringNotContainsString('bgcolor', $output);
    }

    #[Test]
    public function releaseModuleShowsCommentsWithoutButton(): void
    {
        // P05: Kommentare stehen sofort da, kein Knopf "Anzeigen"; P26: Gäste ohne "Anonym posten"
        global $db_handler, $release_id, $user_rights, $settings, $user_details, $users;
        $this->useBootstrapReleaseView();
        $users[1] = ['nick' => 'Poster', 'email' => 'poster@example.org', 'homepage' => ''];
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow(['name' => 'WithComments'])]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([]); // files
        $db_handler->addResult([]); // screens
        $db_handler->addResult([ // comments
            ['comment_id' => 1, 'user_id' => 0, 'titel' => 'Comment1', 'text' => 'Content1', 'time' => 1790000000],
            ['comment_id' => 2, 'user_id' => 1, 'titel' => 'Comment2', 'text' => "Zeile 1\nZeile 2", 'time' => 1790000000],
        ]);
        $release_id = 1;
        $user_details = null;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'Y';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('id="pdlCommentList"', $output);
        $this->assertStringContainsString('Comment1', $output);
        $this->assertStringContainsString('Content1', $output);
        $this->assertStringContainsString('Zeile 1<br>', $output);
        $this->assertStringContainsString('von Poster', $output);
        $this->assertStringNotContainsString('poster@example.org', $output);
        $this->assertStringNotContainsString('Anzeigen', $output);
        $this->assertStringNotContainsString('Anonym', $output);
        $this->assertStringContainsString('registrieren Sie sich</a>, um einen Kommentar zu schreiben.', $output);
    }

    #[Test]
    public function releaseModuleCommentBbcodeLinksAreSafe(): void
    {
        // P19: nur http/https/mailto werden verlinkt
        global $db_handler, $release_id, $user_rights, $settings, $user_details;
        $this->useBootstrapReleaseView();
        $settings['bb_code'] = 'Y';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow()]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([]); // files
        $db_handler->addResult([]); // screens
        $db_handler->addResult([
            ['comment_id' => 1, 'user_id' => 0, 'titel' => '<script>x</script>', 'time' => 1790000000,
             'text' => '[url=javascript:alert(1)]böse[/url] [url=https://ok.example/?a=1&b=2]gut[/url] [img]javascript:alert(2)[/img]'],
        ]);
        $release_id = 1;
        $user_details = null;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'Y';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringNotContainsString('href="javascript', $output);
        $this->assertStringNotContainsString('src="javascript', $output);
        $this->assertStringContainsString('böse', $output);
        $this->assertStringContainsString('<a href="https://ok.example/?a=1&amp;b=2" target="_blank" rel="noopener nofollow ugc">gut</a>', $output);
        $this->assertStringNotContainsString('<script>', $output);
    }

    #[Test]
    public function releaseModuleWithScreenshots(): void
    {
        global $db_handler, $release_id, $user_rights, $settings;
        $this->useBootstrapReleaseView();
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow(['name' => 'WithScreens'])]);
        $db_handler->addResult([]); // views
        $db_handler->addResult([]); // files
        $db_handler->addResult([ // screens ohne Bilddateien
            ['screen_id' => 1, 'release_id' => 1, 'text' => 'Startfenster'],
            ['screen_id' => 2, 'release_id' => 1, 'text' => ''],
        ]);
        $release_id = 1;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('WithScreens', $output);
        $this->assertStringContainsString('href="downloads.php?screen_id=1"', $output);
        $this->assertStringContainsString('Startfenster', $output);
        $this->assertStringContainsString('Screenshot 2', $output);
        // Fehlende Bilddateien: kein kaputtes Bildsymbol
        $this->assertStringNotContainsString('<img src="pdl-gfx/screens/', $output);
    }

    #[Test]
    public function releaseModuleWithBbcodeText(): void
    {
        global $db_handler, $release_id, $user_rights, $settings;
        $this->useBootstrapReleaseView();
        $settings['bb_code'] = 'Y';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([$this->releaseRow(['name' => 'BBTest', 'text' => '[b]Bold desc[/b]'])]);
        $db_handler->addResult([]); // views
        $db_handler->addResult([]); // files
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('BBTest', $output);
        $this->assertStringContainsString('<b>Bold desc</b>', $output);
    }
}
