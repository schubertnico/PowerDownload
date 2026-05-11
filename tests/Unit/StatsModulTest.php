<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Tests\Support\MockDbHandler;

class StatsModulTest extends TestCase
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
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table, $user_rights;

        $user_rights = ['adminaccess' => 'Y'];

        $settings = [
            'dlspeed' => 56, 'date_format' => 'd.m.Y', 'script_file' => 'downloads.php?',
            'spages' => 10, 'perpage' => 10, 'orderby' => 'name', 'orderseq' => 'ASC',
        ];
        $template = [
            'alt_1' => '#FFFFFF', 'alt_2' => '#F0F0F0',
            'footer_bg' => '#CCCCCC', 'header_bg' => '#333333', 'table_border' => '#000000',
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

    private function includeModule(string $file): string
    {
        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table, $user_rights;

        ob_start();
        include $this->incDir . $file;
        return ob_get_clean();
    }

    private function setupStatsDbResults(array $options = []): void
    {
        global $db_handler, $users;

        $db_handler = new MockDbHandler();

        // 1. SHOW TABLE STATUS
        $db_handler->addResult($options['tables'] ?? [
            ['Name' => 'pdl3_files', 'Data_length' => 1024, 'Index_length' => 512, 'Rows' => 10],
            ['Name' => 'pdl3_release', 'Data_length' => 2048, 'Index_length' => 256, 'Rows' => 5],
        ]);

        // 2. MySQL version
        $db_handler->addResult($options['version'] ?? [
            ['Variable_name' => 'version', 'Value' => '8.0.30'],
        ]);

        // 3. User groups
        $db_handler->addResult($options['ugroups'] ?? [
            ['ugroup_name' => 'Admins', 'ugroup_user' => 2],
            ['ugroup_name' => 'Users', 'ugroup_user' => 10],
        ]);

        // 4. Top 10 comment posters
        $db_handler->addResult($options['commenters'] ?? [
            ['user_id' => 1, 'kommentare' => 15],
        ]);

        // 5. Top 10 uploaders
        $db_handler->addResult($options['uploaders'] ?? [
            ['user_id' => 1, 'releases' => 8],
        ]);

        // 6. Top 10 folders
        $db_handler->addResult($options['folders'] ?? [
            ['ordner_id' => 1, 'name' => 'Category1', 'releases' => 5],
        ]);

        // 7. Top 10 by size
        $db_handler->addResult($options['bySize'] ?? [
            ['release_id' => 1, 'name' => 'BigApp', 'size' => 10485760],
        ]);

        // 8. Top 10 by files
        $db_handler->addResult($options['byFiles'] ?? [
            ['release_id' => 1, 'name' => 'ManyFiles', 'files' => 12],
        ]);

        // 9. Top 10 by comments
        $db_handler->addResult($options['byComments'] ?? [
            ['release_id' => 1, 'name' => 'Discussed', 'comments' => 20],
        ]);

        // 10. Top 10 by votes
        $db_handler->addResult($options['byVotes'] ?? [
            ['release_id' => 1, 'name' => 'Popular', 'votes' => 50],
        ]);

        // Set up users for user() function
        $users[1] = ['nick' => 'Admin', 'email' => 'admin@test.com', 'icq' => 0, 'homepage' => ''];
    }

    #[Test]
    public function statsModuleDisplaysServerStats(): void
    {
        $this->setupStatsDbResults();
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4';

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('Server &amp; DB Stats', $output);
        $this->assertStringContainsString('DB Version', $output);
        $this->assertStringContainsString('8.0.30', $output);
        $this->assertStringContainsString('DB Größe', $output);
        $this->assertStringContainsString('Tabellen in der DB', $output);
        $this->assertStringContainsString('2', $output); // 2 tables
        $this->assertStringContainsString('DB Einträge', $output);
        $this->assertStringContainsString('Apache/2.4', $output);
    }

    #[Test]
    public function statsModuleDisplaysUserGroups(): void
    {
        $this->setupStatsDbResults();
        $_SERVER['SERVER_SOFTWARE'] = 'Apache';

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('User &amp; Gruppen', $output);
        $this->assertStringContainsString('Admins', $output);
        $this->assertStringContainsString('Users', $output);
    }

    #[Test]
    public function statsModuleDisplaysTopCommentPosters(): void
    {
        $this->setupStatsDbResults();
        $_SERVER['SERVER_SOFTWARE'] = 'Apache';

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('Top 10 Kommentar-Poster', $output);
        $this->assertStringContainsString('Admin', $output);
        $this->assertStringContainsString('15', $output);
    }

    #[Test]
    public function statsModuleDisplaysTopUploaders(): void
    {
        $this->setupStatsDbResults();
        $_SERVER['SERVER_SOFTWARE'] = 'Apache';

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('Top 10 Uploader', $output);
    }

    #[Test]
    public function statsModuleDisplaysTopFolders(): void
    {
        $this->setupStatsDbResults();
        $_SERVER['SERVER_SOFTWARE'] = 'Apache';

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('Top 10 Ordner', $output);
        $this->assertStringContainsString('Category1', $output);
    }

    #[Test]
    public function statsModuleDisplaysTopBySize(): void
    {
        $this->setupStatsDbResults();
        $_SERVER['SERVER_SOFTWARE'] = 'Apache';

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('Top 10 Release nach Größe', $output);
        $this->assertStringContainsString('BigApp', $output);
    }

    #[Test]
    public function statsModuleDisplaysTopByFiles(): void
    {
        $this->setupStatsDbResults();
        $_SERVER['SERVER_SOFTWARE'] = 'Apache';

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('Top 10 Release nach Files', $output);
        $this->assertStringContainsString('ManyFiles', $output);
    }

    #[Test]
    public function statsModuleDisplaysTopByComments(): void
    {
        $this->setupStatsDbResults();
        $_SERVER['SERVER_SOFTWARE'] = 'Apache';

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('Top 10 Release nach Kommentaren', $output);
        $this->assertStringContainsString('Discussed', $output);
    }

    #[Test]
    public function statsModuleDisplaysTopByVotes(): void
    {
        $this->setupStatsDbResults();
        $_SERVER['SERVER_SOFTWARE'] = 'Apache';

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('Top 10 Release nach Bewertungen', $output);
        $this->assertStringContainsString('Popular', $output);
    }

    #[Test]
    public function statsModuleWithEmptyResults(): void
    {
        global $db_handler, $users;
        $db_handler = new MockDbHandler();
        $users = [];
        $_SERVER['SERVER_SOFTWARE'] = 'Nginx';

        // All empty
        for ($i = 0; $i < 10; $i++) {
            $db_handler->addResult([]);
        }

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('Server &amp; DB Stats', $output);
        $this->assertStringContainsString('Nginx', $output);
    }

    #[Test]
    public function statsModuleWithMultipleUsersAndFolders(): void
    {
        $this->setupStatsDbResults([
            'ugroups' => [
                ['ugroup_name' => 'Group1', 'ugroup_user' => 5],
                ['ugroup_name' => 'Group2', 'ugroup_user' => 15],
                ['ugroup_name' => 'Group3', 'ugroup_user' => 3],
            ],
            'folders' => [
                ['ordner_id' => 1, 'name' => 'Folder1', 'releases' => 10],
                ['ordner_id' => 2, 'name' => 'Folder2', 'releases' => 7],
            ],
            'byVotes' => [
                ['release_id' => 1, 'name' => 'App1', 'votes' => 100],
                ['release_id' => 2, 'name' => 'App2', 'votes' => 50],
            ],
        ]);
        $_SERVER['SERVER_SOFTWARE'] = 'Apache';

        $output = $this->includeModule('pdl_stats.modul.php');

        $this->assertStringContainsString('Group1', $output);
        $this->assertStringContainsString('Group2', $output);
        $this->assertStringContainsString('Folder1', $output);
        $this->assertStringContainsString('App1', $output);
    }
}
