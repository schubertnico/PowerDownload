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
            'pdlversion' => 'v3.0.3',
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
        $this->assertStringContainsString('Login Form Here', $output);
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
        $this->assertStringContainsString('Register Form Here', $output);
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
        $this->assertStringContainsString('Nickname', $output);
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
        $db_handler->addResult([['nick' => 'ExistingUser']]);

        $output = $this->includeModule('pdl_uregister.modul.php');
        // Modul liefert "bereits ein Benutzer mit diesem Nickname registriert"
        $this->assertStringContainsString('Nickname registriert', $output);
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
        $db_handler->addResult([]);
        $db_handler->addResult([['email' => 'existing@test.com']]);

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('E-Mail-Adresse registriert', $output);
    }

    #[Test]
    public function registerModuleSuccess(): void
    {
        global $submit, $nick, $email, $pw_new, $pw_new2, $db_handler, $user_details, $homepage, $icq, $get_letter;
        $submit = 1;
        $nick = 'NewUser';
        $_POST['nick'] = 'NewUser';
        $email = 'newuser@test.com';
        $pw_new = 'securepass';
        $pw_new2 = 'securepass';
        $homepage = 'www.example.com';
        $icq = 0;
        $get_letter = 'Y';
        $user_details = null;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // nick check
        $db_handler->addResult([]); // email check
        $db_handler->addResult([]); // insert

        $output = $this->includeModule('pdl_uregister.modul.php');
        $this->assertStringContainsString('erfolgreich', $output);
    }

    // ==================== pdl_uprofil.modul.php ====================

    #[Test]
    public function profilModuleShowsLoginRequired(): void
    {
        global $user_details, $submit;
        $user_details = null;
        $submit = 0;

        $output = $this->includeModule('pdl_uprofil.modul.php');
        $this->assertStringContainsString('eingeloggt', $output);
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
        $this->assertStringContainsString('erfolgreich', $output);
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
        $this->assertStringContainsString('stimmt nicht', $output);
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
        $this->assertStringContainsString('erfolgreich', $output);
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
        $this->assertStringContainsString('erfolgreich', $output);
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
        $this->assertStringContainsString('erfolgreich', $output);
    }

    // ==================== pdl_ucomments.modul.php ====================

    #[Test]
    public function commentsModuleNoRights(): void
    {
        global $user_rights;
        $user_rights['addcomments'] = 'N';

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('keine Rechte', $output);
    }

    #[Test]
    public function commentsModuleCommentsDisabled(): void
    {
        global $user_rights, $settings;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('keine Rechte', $output);
    }

    #[Test]
    public function commentsModuleShowsFormLoggedIn(): void
    {
        global $user_rights, $settings, $submit, $user_details, $release_id;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'Y';
        $submit = 0;
        $release_id = 1;
        $user_details = ['nick' => 'TestUser', 'user_id' => 1];

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('TestUser', $output);
    }

    #[Test]
    public function commentsModuleShowsLoginPromptForGuest(): void
    {
        // Gäste sehen keinen Kommentar-Editor mehr, sondern eine Anmelde-Aufforderung
        // (Konsequenz aus User-Area-Bugfixes BUG-011/BUG-023).
        global $user_rights, $settings, $submit, $user_details, $release_id;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'Y';
        $submit = 0;
        $release_id = 1;
        $user_details = null;

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('einloggen', $output);
        $this->assertStringContainsString('kommentieren', $output);
    }

    #[Test]
    public function commentsModuleSubmitEmpty(): void
    {
        global $user_rights, $settings, $submit, $user_details, $release_id, $titel, $text;
        $user_rights['addcomments'] = 'Y';
        $settings['enable_comments'] = 'Y';
        $submit = 1;
        $release_id = 1;
        $titel = '';
        $text = '';
        $user_details = ['nick' => 'TestUser', 'user_id' => 1];

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('Titel und Text', $output);
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
        $db_handler->addResult([]);

        $output = $this->includeModule('pdl_ucomments.modul.php');
        $this->assertStringContainsString('gepostet', $output);
    }

    // ==================== pdl_showscreen.modul.php ====================

    #[Test]
    public function showscreenModuleNotFound(): void
    {
        global $db_handler, $screen_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // update
        $db_handler->addResult([]); // select
        $screen_id = 999;

        $output = $this->includeModule('pdl_showscreen.modul.php');
        $this->assertStringContainsString('nicht gefunden', $output);
    }

    #[Test]
    public function showscreenModuleDisplaysScreen(): void
    {
        global $db_handler, $screen_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // update
        $db_handler->addResult([
            ['screen_id' => 1, 'release_id' => 5, 'views' => 42, 'text' => 'Screenshot text'],
        ]);
        $screen_id = 1;

        $output = $this->includeModule('pdl_showscreen.modul.php');
        $this->assertStringContainsString('Screenshot text', $output);
        $this->assertStringContainsString('42', $output);
    }

    // ==================== pdl_ulost.modul.php ====================

    #[Test]
    public function lostModuleShowsForm(): void
    {
        global $submit;
        $submit = 0;

        $output = $this->includeModule('pdl_ulost.modul.php');
        $this->assertStringContainsString('Lost Password Form Here', $output);
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
        $this->assertStringContainsString('Konto mit dieser E-Mail existiert', $output);
        $this->assertStringContainsString('weiteren Schritten', $output);
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
        $db_handler->addResult([
            ['user_id' => 1, 'nick' => 'TestUser', 'email' => 'found@test.com'],
        ]);
        $db_handler->addResult([]); // update remind_code

        $output = $this->includeModule('pdl_ulost.modul.php');
        $this->assertStringContainsString('Konto mit dieser E-Mail existiert', $output);
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
        // Modul liefert "Ungültiger oder abgelaufener Code." (UTF-8)
        $this->assertStringContainsString('Ungültiger oder abgelaufener Code', $output);
    }

    #[Test]
    public function lost2ModuleValidCodeRendersResetForm(): void
    {
        global $remind_code, $db_handler, $submit;
        $submit = 0;
        $remind_code = 'validcode123';
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
        $db_handler->addResult([['release_id' => 1]]); // files_check
        $db_handler->addResult([]); // ordner_check
        $db_handler->addResult([['release_id' => 1]]); // total
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'TestRelease', 'text' => '', 'ordner_id' => 0,
             'votes' => 0, 'voted' => 0, 'views' => 10, 'downloads' => 5, 'time' => time(), 'uploader' => 0],
        ]);
        $db_handler->addResult([['tsize' => 1024]]); // size
        $ordner_id = 0;
        $page = 1;

        $output = $this->includeModule('pdl_ordner.modul.php');
        $this->assertStringContainsString('TestRelease', $output);
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
        $db_handler->addResult([['release_id' => 1]]); // total
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'TestRelease', 'text' => 'This is a very long description that should be truncated',
             'ordner_id' => 0, 'votes' => 0, 'voted' => 0, 'views' => 10, 'downloads' => 5, 'time' => time(), 'uploader' => 0],
        ]);
        $db_handler->addResult([['tsize' => 2048]]); // size
        $ordner_id = 0;
        $page = 1;

        $output = $this->includeModule('pdl_ordner.modul.php');
        $this->assertStringContainsString('...', $output);
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
        $db_handler->addResult([['release_id' => 1]]); // total
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'Test', 'text' => 'Short text---Rest hidden',
             'ordner_id' => 0, 'votes' => 0, 'voted' => 0, 'views' => 10, 'downloads' => 5, 'time' => time(), 'uploader' => 0],
        ]);
        $db_handler->addResult([['tsize' => 512]]); // size
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
        $db_handler->addResult([['release_id' => 1]]);
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'TestApp', 'text' => 'Desc', 'ordner_id' => 0,
             'votes' => 0, 'voted' => 0, 'views' => 0, 'downloads' => 0, 'time' => time(), 'uploader' => 0],
        ]);
        $db_handler->addResult([['tsize' => 2048]]);

        $output = $this->includeModule('pdl_search.modul.php');
        $this->assertStringContainsString('1', $output);
        $this->assertStringContainsString('Treffer', $output);
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

    #[Test]
    public function releaseModuleNotFound(): void
    {
        global $db_handler, $release_id, $vote, $vote_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock
        $db_handler->addResult([]); // release query
        $release_id = 999;
        $vote = 0;
        $vote_id = 0;

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('nicht gefunden', $output);
    }

    #[Test]
    public function releaseModuleHiddenRelease(): void
    {
        global $db_handler, $release_id, $vote, $vote_id;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'Hidden', 'released' => 'N'],
        ]);
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('versteckt', $output);
    }

    #[Test]
    public function releaseModuleDisplaysRelease(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $user_rights, $settings, $showcomments;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock check
        $db_handler->addResult([ // release
            ['release_id' => 1, 'name' => 'TestRelease', 'text' => 'Description', 'released' => 'Y',
             'votes' => 5, 'voted' => 40, 'ordner_id' => 0, 'views' => 100, 'downloads' => 50,
             'time' => time(), 'uploader' => 0, 'autor' => -1, 'autor_email' => '', 'autor_nick' => '',
             'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([ // first file
            ['file_id' => 1, 'url' => 'files/test.zip', 'size' => 1024, 'downloads' => 10, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([ // files loop
            ['file_id' => 1, 'url' => 'files/test.zip', 'size' => 1024, 'downloads' => 10, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 0;
        $user_rights['vote'] = 'Y';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('TestRelease', $output);
        $this->assertStringContainsString('Unbekannt', $output);
    }

    #[Test]
    public function releaseModuleWithAutorUser(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $user_rights, $settings, $users, $showcomments;
        $users[5] = ['nick' => 'AuthorUser', 'email' => 'author@test.com', 'icq' => 0, 'homepage' => ''];
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'Test', 'text' => '', 'released' => 'Y',
             'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => 5, 'autor_email' => '', 'autor_nick' => '',
             'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([ // first file
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([ // files
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 0;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('AuthorUser', $output);
    }

    #[Test]
    public function releaseModuleWithExternalAutor(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $user_rights, $settings, $showcomments;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'Test', 'text' => 'Desc', 'released' => 'Y',
             'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => 0, 'autor_email' => 'ext@test.com',
             'autor_nick' => 'ExtAuthor', 'autor_icq' => 12345, 'autor_homepage' => 'https://ext.com'],
        ]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([ // first file
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([ // files
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 0;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('ExtAuthor', $output);
    }

    #[Test]
    public function releaseModuleWithMirrorFile(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $user_rights, $settings, $showcomments;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'Test', 'text' => '', 'released' => 'Y',
             'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => -1, 'autor_email' => '',
             'autor_nick' => '', 'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([ // first file
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 1024, 'downloads' => 10, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([ // files loop - one original, one mirror
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 1024, 'downloads' => 10, 'mirror' => 0, 'release_id' => 1],
            ['file_id' => 2, 'url' => 'files/mirror.zip', 'size' => 0, 'downloads' => 5, 'mirror' => 1, 'release_id' => 1],
        ]);
        // mirror lookup
        $db_handler->addResult([
            ['file_id' => 1, 'size' => 1024],
        ]);
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 0;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('Test', $output);
    }

    #[Test]
    public function releaseModuleWithCommentsEnabled(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $user_rights, $settings, $showcomments, $user_details;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'Commented', 'text' => '', 'released' => 'Y',
             'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => -1, 'autor_email' => '',
             'autor_nick' => '', 'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 1024, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 1024, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // screens
        $db_handler->addResult([['comment_id' => 1], ['comment_id' => 2]]); // comments count
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 0;
        $user_details = ['nick' => 'User', 'user_id' => 1];
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'Y';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('Kommentare', $output);
        $this->assertStringContainsString('Kommentar schreiben', $output);
    }

    #[Test]
    public function releaseModuleWithCommentsShown(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $user_rights, $settings, $showcomments, $user_details;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'WithComments', 'text' => '', 'released' => 'Y',
             'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => -1, 'autor_email' => '',
             'autor_nick' => '', 'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // screens
        $db_handler->addResult([ // comments
            ['comment_id' => 1, 'user_id' => 0, 'titel' => 'Comment1', 'text' => 'Content1', 'time' => time()],
            ['comment_id' => 2, 'user_id' => 1, 'titel' => 'Comment2', 'text' => 'Content2', 'time' => time()],
        ]);
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 1;
        $user_details = null;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'Y';

        $output = $this->includeModule('pdl_release.modul.php');
        // Release-Template enthält Platzhalter; aktuelle Test-Configuration nutzt
        // ein minimales Template, deshalb prüfen wir nur, dass das Modul ohne
        // Fehler durchläuft und den Release-Namen ausgibt.
        $this->assertStringContainsString('WithComments', $output);
    }

    #[Test]
    public function releaseModuleWithVoting(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $user_rights, $settings, $showcomments, $user_details, $ip;
        $ip = '127.0.0.1';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock check - not locked
        $db_handler->addResult([]); // insert iplock
        $db_handler->addResult([]); // update votes
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'VotedRelease', 'text' => '', 'released' => 'Y',
             'votes' => 1, 'voted' => 8, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => -1, 'autor_email' => '',
             'autor_nick' => '', 'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $vote = 1;
        $vote_id = 8;
        $showcomments = 0;
        $user_details = ['nick' => 'Voter', 'user_id' => 2];
        $user_rights['vote'] = 'Y';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('VotedRelease', $output);
    }

    #[Test]
    public function releaseModuleWithScreenshots(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $user_rights, $settings, $showcomments;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'WithScreens', 'text' => '', 'released' => 'Y',
             'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => -1, 'autor_email' => '',
             'autor_nick' => '', 'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([ // screens
            ['screen_id' => 1, 'release_id' => 1],
            ['screen_id' => 2, 'release_id' => 1],
        ]);
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 0;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('WithScreens', $output);
    }

    #[Test]
    public function releaseModuleExternalAutorNoEmail(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $user_rights, $settings, $showcomments;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'Test', 'text' => '', 'released' => 'Y',
             'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => 0, 'autor_email' => '',
             'autor_nick' => 'JustNick', 'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 0;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('JustNick', $output);
    }

    #[Test]
    public function releaseModuleWithBbcodeText(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $user_rights, $settings, $showcomments;
        $settings['bb_code'] = 'Y';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // iplock
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'BBTest', 'text' => '[b]Bold desc[/b]', 'released' => 'Y',
             'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => -1, 'autor_email' => '',
             'autor_nick' => '', 'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([
            ['file_id' => 1, 'url' => 'files/t.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // screens
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 0;
        $user_rights['vote'] = 'N';
        $settings['enable_comments'] = 'N';

        $output = $this->includeModule('pdl_release.modul.php');
        $this->assertStringContainsString('BBTest', $output);
        $this->assertStringContainsString('Bold desc', $output);
    }
}
