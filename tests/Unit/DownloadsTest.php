<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Tests\Support\MockDbHandler;

class DownloadsTest extends TestCase
{
    private string $incDir;

    protected function setUp(): void
    {
        $this->incDir = dirname(__DIR__, 2) . '/pdl-inc/';
        $this->setupGlobals();
    }

    private function setupGlobals(): void
    {
        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table,
               $user_details, $user_rights, $rendertime1,
               $ordner_id, $release_id, $screen_id, $usercenter, $show_search, $show_stats,
               $wrong_referer, $wrong_rights, $subfiles, $subdirs, $release;

        $settings = [
            'dlspeed' => 56, 'date_format' => 'd.m.Y', 'script_file' => 'downloads.php?',
            'spages' => 10, 'perpage' => 10, 'orderby' => 'name', 'orderseq' => 'ASC',
            'enable_treeview' => 'N', 'enable_extrernadmin' => 'N',
            'enable_search' => 'N', 'enable_comments' => 'N',
            'debug' => false, 'showcopy' => false, 'pdlversion' => 'v3.0.3',
            'trenn_durch' => '', 'trenn_string' => '', 'bb_code' => 'N', 'smilies' => 'N',
            'glossary' => 'N', 'badwords_releases' => 'N', 'html_releases' => 'N',
            'shortname' => 0,
        ];
        $template = [
            'alt_1' => '#FFF', 'alt_2' => '#F0F', 'footer_bg' => '#CCC',
            'header_bg' => '#333', 'table_border' => '#000', 'all_width' => '100%',
            'release_row' => '{name}', 'release_box' => '{rows}',
            'ordner_row' => '{name}', 'ordner_box' => '{rows}',
            'file_detail' => '{name} {text} {autor}', 'dfiles_row' => '{filename}',
            'comments' => '{titel}', 'top_row' => '{name}', 'top_box' => '{rows}',
            'own_footer' => '',
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

        $sql_table = [
            'comments' => 'pdl3_comments', 'files' => 'pdl3_files',
            'iplock' => 'pdl3_iplock', 'ordner' => 'pdl3_ordner',
            'release' => 'pdl3_release', 'replacements' => 'pdl3_replacements',
            'screens' => 'pdl3_screens', 'settings' => 'pdl3_settings',
            'template' => 'pdl3_template', 'user' => 'pdl3_user',
            'usergroup' => 'pdl3_usergroup',
        ];

        $db_handler = new MockDbHandler();
    }

    private function includeDownloads(): string
    {
        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table,
               $user_details, $user_rights, $rendertime1,
               $ordner_id, $release_id, $screen_id, $usercenter, $show_search, $show_stats,
               $wrong_referer, $wrong_rights, $subfiles, $subdirs, $release,
               $submit, $nick, $pw, $email, $text, $titel, $vote, $vote_id,
               $showcomments, $remind_code, $ip, $login_error;

        ob_start();
        include $this->incDir . 'pdl_downloads.inc.php';
        return ob_get_clean();
    }

    #[Test]
    public function downloadsShowsIndexPage(): void
    {
        // pdl_downloads.inc.php rendert den Inhaltsbereich (Treeview-Breadcrumb +
        // Ordner-Modul). Nav/Logo/Footer liegen im Layout-Helper und werden
        // hier nicht eingebunden.
        global $db_handler, $ordner_id;
        $ordner_id = 0;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);
        $db_handler->addResult([]);

        $output = $this->includeDownloads();

        $this->assertStringContainsString('Index', $output);
        $this->assertStringContainsString('Navigationspfad', $output);
    }

    #[Test]
    public function downloadsShowsEmptyFolderMessage(): void
    {
        global $db_handler, $user_details;
        $user_details = null;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);
        $db_handler->addResult([]);

        $output = $this->includeDownloads();

        // Aktueller leerer-Ordner-Block trägt eine Überschrift und einen
        // Hinweistext, die beide den Stand klar kommunizieren.
        $this->assertStringContainsString('Dieser Ordner ist noch leer', $output);
        $this->assertStringContainsString('keine Releases', $output);
    }

    #[Test]
    public function downloadsRendersTreeviewBreadcrumbForLoggedInUser(): void
    {
        global $db_handler, $user_details;
        $user_details = ['nick' => 'TestUser', 'user_id' => 1];
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);
        $db_handler->addResult([]);

        $output = $this->includeDownloads();

        $this->assertStringContainsString('aria-current="page"', $output);
        $this->assertStringContainsString('Index', $output);
    }

    #[Test]
    public function downloadsShowsAdminLinkForAdmin(): void
    {
        // Im inneren Inhalt erscheint der "Admin-Optionen"-Block nur, wenn der
        // ExternAdmin-Modus aktiviert ist (sonst kommt der Admin-Center-Link
        // ausschliesslich im Layout-Header).
        global $db_handler, $user_details, $user_rights, $settings;
        $user_details = ['nick' => 'Admin', 'user_id' => 1];
        $user_rights = [
            'adminaccess' => 'Y', 'editfiles' => 'Y', 'delfiles' => 'Y',
            'adddirs' => 'Y', 'editdirs' => 'Y', 'deldirs' => 'Y',
            'download' => 'Y', 'vote' => 'N', 'addcomments' => 'N',
        ];
        $settings['enable_extrernadmin'] = 'Y';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);
        $db_handler->addResult([]);

        $output = $this->includeDownloads();

        $this->assertStringContainsString('Admin-Optionen', $output);
    }

    #[Test]
    public function downloadsShowsWrongRefererMessage(): void
    {
        global $db_handler, $wrong_referer;
        $wrong_referer = 1;
        $db_handler = new MockDbHandler();

        $output = $this->includeDownloads();

        $this->assertStringContainsString('illegal', $output);
    }

    #[Test]
    public function downloadsShowsWrongRightsMessage(): void
    {
        global $db_handler, $wrong_rights;
        $wrong_rights = 1;
        $db_handler = new MockDbHandler();

        $output = $this->includeDownloads();

        $this->assertStringContainsString('keine Berechtigung', $output);
    }

    #[Test]
    public function downloadsShowsUnknownModule(): void
    {
        global $db_handler, $usercenter;
        $usercenter = 'nonexistent';
        $db_handler = new MockDbHandler();

        $output = $this->includeDownloads();

        $this->assertStringContainsString('Unbekanntes Modul', $output);
    }

    #[Test]
    public function downloadsWithSearchModule(): void
    {
        global $db_handler, $show_search, $settings, $submit;
        $show_search = 1;
        $settings['enable_search'] = 'N';
        $submit = 0;
        $db_handler = new MockDbHandler();

        $output = $this->includeDownloads();

        $this->assertStringContainsString('deaktiviert', $output);
    }

    #[Test]
    public function downloadsWithStatsModule(): void
    {
        global $db_handler, $show_stats, $users;
        $show_stats = 1;
        $users[1] = ['nick' => 'Admin', 'email' => 'a@t.com', 'icq' => 0, 'homepage' => ''];
        $_SERVER['SERVER_SOFTWARE'] = 'TestServer';
        $db_handler = new MockDbHandler();
        // stats module needs 10 query results
        for ($i = 0; $i < 10; $i++) {
            $db_handler->addResult([]);
        }

        $output = $this->includeDownloads();

        // HTML-Ausgabe escaped das Ampersand: "Server &amp; DB Stats"
        $this->assertStringContainsString('Server &amp; DB Stats', $output);
    }

    #[Test]
    public function downloadsWithLoginModule(): void
    {
        global $db_handler, $usercenter, $template;
        $usercenter = 'login';
        $template['ulogin_form'] = 'LOGIN_FORM_CONTENT';
        $db_handler = new MockDbHandler();

        $output = $this->includeDownloads();

        $this->assertStringContainsString('LOGIN_FORM_CONTENT', $output);
    }

    #[Test]
    public function downloadsWithReleaseId(): void
    {
        global $db_handler, $release_id, $vote, $vote_id, $showcomments;
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 0;
        $db_handler = new MockDbHandler();
        // For downloads.inc.php: release lookup
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'TestRel', 'ordner_id' => 0],
        ]);
        // For treeview_pfeil (via downloads.inc.php) - not called when treeview=N but ordner_id=0
        // For release module: iplock
        $db_handler->addResult([]);
        // release data
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'TestRel', 'text' => '', 'released' => 'Y',
             'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => -1, 'autor_email' => '',
             'autor_nick' => '', 'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views update
        $db_handler->addResult([ // first file
            ['file_id' => 1, 'url' => 'f.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([ // files
            ['file_id' => 1, 'url' => 'f.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1],
        ]);
        $db_handler->addResult([]); // screens

        $output = $this->includeDownloads();

        $this->assertStringContainsString('TestRel', $output);
    }

    #[Test]
    public function downloadsWithDebugEnabled(): void
    {
        global $db_handler, $settings;
        $settings['debug'] = true;
        $settings['showcopy'] = true;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);
        $db_handler->addResult([]);

        $output = $this->includeDownloads();

        // Im Innenbereich nur die Treeview-Breadcrumb sichtbar; Renderzeit/SQL
        // liegen im Layout-Footer. Wir prüfen, dass kein Fehler auftritt.
        $this->assertStringContainsString('Index', $output);
    }

    #[Test]
    public function downloadsWithTreeviewEnabled(): void
    {
        global $db_handler, $settings;
        $settings['enable_treeview'] = 'Y';
        $db_handler = new MockDbHandler();
        // treeview_ordner(0, '') - returns no folders
        $db_handler->addResult([]);
        // ordner module: files_check, ordner_check
        $db_handler->addResult([]);
        $db_handler->addResult([]);

        $output = $this->includeDownloads();

        $this->assertStringContainsString('folder_open.gif', $output);
        $this->assertStringContainsString('Index', $output);
    }

    #[Test]
    public function downloadsWithTreeviewEnabledNotIndex(): void
    {
        global $db_handler, $settings, $ordner_id;
        $settings['enable_treeview'] = 'Y';
        $ordner_id = 5;
        $db_handler = new MockDbHandler();
        // treeview_ordner(0, '')
        $db_handler->addResult([]);
        // ordner module: files_check, ordner_check
        $db_handler->addResult([]);
        $db_handler->addResult([]);

        $output = $this->includeDownloads();

        $this->assertStringContainsString('folder.gif', $output);
        $this->assertStringContainsString('ordner_id=0', $output);
    }

    #[Test]
    public function downloadsWithoutTreeviewNonIndex(): void
    {
        global $db_handler, $ordner_id;
        $ordner_id = 5;
        $db_handler = new MockDbHandler();
        // treeview_pfeil needs folder data
        $db_handler->addResult([
            ['ordner_id' => 5, 'sordner_id' => 0, 'name' => 'Folder5'],
        ]);
        // ordner module: files_check, ordner_check
        $db_handler->addResult([]);
        $db_handler->addResult([]);

        $output = $this->includeDownloads();

        $this->assertStringContainsString('Folder5', $output);
    }

    #[Test]
    public function downloadsWithExternAdmin(): void
    {
        global $db_handler, $settings, $user_details, $user_rights, $ordner_id;
        $settings['enable_extrernadmin'] = 'Y';
        $user_details = ['nick' => 'Admin', 'user_id' => 1];
        $user_rights = [
            'adminaccess' => 'Y', 'editfiles' => 'Y', 'delfiles' => 'Y',
            'adddirs' => 'Y', 'editdirs' => 'Y', 'deldirs' => 'Y',
            'download' => 'Y', 'vote' => 'N', 'addcomments' => 'N',
        ];
        $ordner_id = 5;
        $db_handler = new MockDbHandler();
        // treeview_pfeil
        $db_handler->addResult([
            ['ordner_id' => 5, 'sordner_id' => 0, 'name' => 'Folder5'],
        ]);
        // ordner module
        $db_handler->addResult([]);
        $db_handler->addResult([]);

        $output = $this->includeDownloads();

        $this->assertStringContainsString('Admin-Optionen', $output);
        $this->assertStringContainsString('Sub-Ordner hinzufügen', $output);
        $this->assertStringContainsString('Ordner editieren', $output);
        $this->assertStringContainsString('Ordner löschen', $output);
    }

    #[Test]
    public function downloadsWithExternAdminRelease(): void
    {
        global $db_handler, $settings, $user_details, $user_rights, $release_id, $vote, $vote_id, $showcomments;
        $settings['enable_extrernadmin'] = 'Y';
        $user_details = ['nick' => 'Admin', 'user_id' => 1];
        $user_rights = [
            'adminaccess' => 'Y', 'editfiles' => 'Y', 'delfiles' => 'Y',
            'download' => 'Y', 'vote' => 'N', 'addcomments' => 'N',
        ];
        $release_id = 1;
        $vote = 0;
        $vote_id = 0;
        $showcomments = 0;
        $db_handler = new MockDbHandler();
        // release lookup for downloads.inc.php
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'AdminRel', 'ordner_id' => 0],
        ]);
        // release module: iplock
        $db_handler->addResult([]);
        // release data
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'AdminRel', 'text' => '', 'released' => 'Y',
             'votes' => 0, 'voted' => 0, 'ordner_id' => 0, 'views' => 0, 'downloads' => 0,
             'time' => time(), 'uploader' => 0, 'autor' => -1, 'autor_email' => '',
             'autor_nick' => '', 'autor_icq' => 0, 'autor_homepage' => ''],
        ]);
        $db_handler->addResult([]); // views
        $db_handler->addResult([['file_id' => 1, 'url' => 'f.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1]]);
        $db_handler->addResult([['file_id' => 1, 'url' => 'f.zip', 'size' => 512, 'downloads' => 0, 'mirror' => 0, 'release_id' => 1]]);
        $db_handler->addResult([]); // screens

        $output = $this->includeDownloads();

        $this->assertStringContainsString('Admin-Optionen', $output);
        $this->assertStringContainsString('Release editieren', $output);
        $this->assertStringContainsString('Datei hinzufügen', $output);
    }

    #[Test]
    public function downloadsWithScreenId(): void
    {
        global $db_handler, $screen_id;
        $screen_id = 1;
        $db_handler = new MockDbHandler();
        // downloads.inc.php: screen lookup
        $db_handler->addResult([['release_id' => 1]]);
        // release lookup
        $db_handler->addResult([['release_id' => 1, 'name' => 'ScreenRel', 'ordner_id' => 0]]);
        // showscreen module: update views, select screen
        $db_handler->addResult([]);
        $db_handler->addResult([
            ['screen_id' => 1, 'release_id' => 1, 'views' => 10, 'text' => 'Screen text'],
        ]);

        $output = $this->includeDownloads();

        $this->assertStringContainsString('Screen text', $output);
    }
}
