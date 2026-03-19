<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Tests\Support\MockDbHandler;

class WidgetsTest extends TestCase
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
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table;

        $settings = [
            'dlspeed' => 56, 'date_format' => 'd.m.Y', 'script_file' => 'downloads.php?',
            'spages' => 10, 'perpage' => 10, 'orderby' => 'name', 'orderseq' => 'ASC',
            'top_count' => 3, 'shortname' => 0,
            'trenn_durch' => '', 'trenn_string' => '', 'trenn_zeichen' => 100,
            'bb_code' => 'N', 'smilies' => 'N', 'glossary' => 'N',
            'badwords_releases' => 'N', 'html_releases' => 'N',
            'installed' => time() - 86400,
        ];
        $template = [
            'alt_1' => '#FFF', 'alt_2' => '#F0F',
            'footer_bg' => '#CCC', 'header_bg' => '#333', 'table_border' => '#000',
            'top_row' => '{name} {count}',
            'top_box' => 'TOP:{rows}',
            'flop_row' => '{name} {count}',
            'flop_box' => 'FLOP:{rows}',
            'latest_row' => '{name} {count}',
            'latest_box' => 'LATEST:{rows}',
            'rated_row' => '{name} {count} {vote}',
            'rated_box' => 'RATED:{rows}',
            'stats' => 'FILES:{files} SIZE:{size} DL:{downloads} TRAFFIC:{traffic} DURCH_DL:{durch_downloads} DURCH_TRAFFIC:{durch_traffic}',
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

    private function includeWidget(string $file): string
    {
        global $settings, $template, $smilies, $glossary, $badwords, $users, $alt_switch,
               $page, $list, $total, $install, $inadmin, $db_handler, $sql_table;

        ob_start();
        include $this->incDir . $file;
        return ob_get_clean();
    }

    private function createReleaseRows(int $count): array
    {
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'release_id' => $i, 'name' => "Release$i", 'text' => "Desc $i",
                'downloads' => $i * 10, 'size' => $i * 1024, 'votes' => $i,
                'voted' => $i * 8, 'views' => $i * 5, 'time' => time() - $i * 3600,
                'ordner_id' => 0, 'uploader' => 0,
            ];
        }
        return $rows;
    }

    // ==================== pdl_top.inc.php ====================

    #[Test]
    public function topWidgetNoReleases(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]); // no releases

        $output = $this->includeWidget('pdl_top.inc.php');
        $this->assertStringContainsString('TOP:', $output);
    }

    #[Test]
    public function topWidgetWithReleases(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        $db_handler->addResult($this->createReleaseRows(3));

        $output = $this->includeWidget('pdl_top.inc.php');
        $this->assertStringContainsString('TOP:', $output);
        $this->assertStringContainsString('Release1', $output);
        $this->assertStringContainsString('Release2', $output);
        $this->assertStringContainsString('Release3', $output);
    }

    #[Test]
    public function topWidgetWithTextTemplate(): void
    {
        global $db_handler, $template;
        $template['top_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult($this->createReleaseRows(2));

        $output = $this->includeWidget('pdl_top.inc.php');
        $this->assertStringContainsString('Desc 1', $output);
    }

    #[Test]
    public function topWidgetWithShortnameEnabled(): void
    {
        global $db_handler, $settings;
        $settings['shortname'] = 8;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'VeryLongReleaseName', 'text' => '', 'downloads' => 10,
             'size' => 1024, 'votes' => 0, 'voted' => 0, 'views' => 0, 'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_top.inc.php');
        $this->assertStringContainsString('...', $output);
    }

    #[Test]
    public function topWidgetWithTextTruncationByChar(): void
    {
        global $db_handler, $settings, $template;
        $settings['trenn_durch'] = 'zeichen';
        $settings['trenn_zeichen'] = 5;
        $template['top_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => 'A very long description text here',
             'downloads' => 10, 'size' => 1024, 'votes' => 0, 'voted' => 0, 'views' => 0,
             'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_top.inc.php');
        $this->assertStringContainsString('...', $output);
    }

    #[Test]
    public function topWidgetWithTextTruncationByString(): void
    {
        global $db_handler, $settings, $template;
        $settings['trenn_durch'] = 'string';
        $settings['trenn_string'] = '---';
        $template['top_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => 'Short part---Long hidden part',
             'downloads' => 10, 'size' => 1024, 'votes' => 0, 'voted' => 0, 'views' => 0,
             'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_top.inc.php');
        $this->assertStringContainsString('Short part', $output);
        $this->assertStringNotContainsString('Long hidden part', $output);
    }

    #[Test]
    public function topWidgetWithEmptyText(): void
    {
        global $db_handler, $template;
        $template['top_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => '',
             'downloads' => 10, 'size' => 1024, 'votes' => 0, 'voted' => 0, 'views' => 0,
             'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_top.inc.php');
        $this->assertStringContainsString('N/A', $output);
    }

    // ==================== pdl_flop.inc.php ====================

    #[Test]
    public function flopWidgetNoReleases(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeWidget('pdl_flop.inc.php');
        $this->assertStringContainsString('FLOP:', $output);
    }

    #[Test]
    public function flopWidgetWithReleases(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        $db_handler->addResult($this->createReleaseRows(2));

        $output = $this->includeWidget('pdl_flop.inc.php');
        $this->assertStringContainsString('Release1', $output);
    }

    #[Test]
    public function flopWidgetWithTextTemplate(): void
    {
        global $db_handler, $template;
        $template['flop_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult($this->createReleaseRows(1));

        $output = $this->includeWidget('pdl_flop.inc.php');
        $this->assertStringContainsString('Desc 1', $output);
    }

    #[Test]
    public function flopWidgetWithShortname(): void
    {
        global $db_handler, $settings;
        $settings['shortname'] = 8;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'VeryLongReleaseName', 'text' => '', 'downloads' => 0,
             'size' => 512, 'votes' => 0, 'voted' => 0, 'views' => 0, 'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_flop.inc.php');
        $this->assertStringContainsString('...', $output);
    }

    #[Test]
    public function flopWidgetEmptyText(): void
    {
        global $db_handler, $template;
        $template['flop_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => '', 'downloads' => 0,
             'size' => 512, 'votes' => 0, 'voted' => 0, 'views' => 0, 'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_flop.inc.php');
        $this->assertStringContainsString('N/A', $output);
    }

    #[Test]
    public function flopWidgetTextTruncationByChar(): void
    {
        global $db_handler, $settings, $template;
        $settings['trenn_durch'] = 'zeichen';
        $settings['trenn_zeichen'] = 5;
        $template['flop_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => 'A very long description',
             'downloads' => 0, 'size' => 512, 'votes' => 0, 'voted' => 0, 'views' => 0,
             'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_flop.inc.php');
        $this->assertStringContainsString('...', $output);
    }

    #[Test]
    public function flopWidgetTextTruncationByString(): void
    {
        global $db_handler, $settings, $template;
        $settings['trenn_durch'] = 'string';
        $settings['trenn_string'] = '---';
        $template['flop_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => 'Visible---Hidden',
             'downloads' => 0, 'size' => 512, 'votes' => 0, 'voted' => 0, 'views' => 0,
             'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_flop.inc.php');
        $this->assertStringContainsString('Visible', $output);
    }

    // ==================== pdl_latest.inc.php ====================

    #[Test]
    public function latestWidgetNoReleases(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeWidget('pdl_latest.inc.php');
        $this->assertStringContainsString('LATEST:', $output);
    }

    #[Test]
    public function latestWidgetWithReleases(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        $db_handler->addResult($this->createReleaseRows(3));

        $output = $this->includeWidget('pdl_latest.inc.php');
        $this->assertStringContainsString('Release1', $output);
        $this->assertStringContainsString('Release3', $output);
    }

    #[Test]
    public function latestWidgetWithTextTemplate(): void
    {
        global $db_handler, $template;
        $template['latest_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult($this->createReleaseRows(1));

        $output = $this->includeWidget('pdl_latest.inc.php');
        $this->assertStringContainsString('Desc 1', $output);
    }

    #[Test]
    public function latestWidgetWithShortname(): void
    {
        global $db_handler, $settings;
        $settings['shortname'] = 8;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'VeryLongReleaseName', 'text' => '', 'downloads' => 0,
             'size' => 512, 'votes' => 0, 'voted' => 0, 'views' => 0, 'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_latest.inc.php');
        $this->assertStringContainsString('...', $output);
    }

    #[Test]
    public function latestWidgetEmptyText(): void
    {
        global $db_handler, $template;
        $template['latest_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => '', 'downloads' => 0,
             'size' => 512, 'votes' => 0, 'voted' => 0, 'views' => 0, 'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_latest.inc.php');
        $this->assertStringContainsString('N/A', $output);
    }

    #[Test]
    public function latestWidgetTextTruncByChar(): void
    {
        global $db_handler, $settings, $template;
        $settings['trenn_durch'] = 'zeichen';
        $settings['trenn_zeichen'] = 5;
        $template['latest_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => 'Long description text',
             'downloads' => 0, 'size' => 512, 'votes' => 0, 'voted' => 0, 'views' => 0,
             'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_latest.inc.php');
        $this->assertStringContainsString('...', $output);
    }

    #[Test]
    public function latestWidgetTextTruncByString(): void
    {
        global $db_handler, $settings, $template;
        $settings['trenn_durch'] = 'string';
        $settings['trenn_string'] = '---';
        $template['latest_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => 'Short---Rest',
             'downloads' => 0, 'size' => 512, 'votes' => 0, 'voted' => 0, 'views' => 0,
             'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_latest.inc.php');
        $this->assertStringContainsString('Short', $output);
    }

    // ==================== pdl_rated.inc.php ====================

    #[Test]
    public function ratedWidgetNoReleases(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([]);

        $output = $this->includeWidget('pdl_rated.inc.php');
        $this->assertStringContainsString('RATED:', $output);
    }

    #[Test]
    public function ratedWidgetWithReleases(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        $db_handler->addResult($this->createReleaseRows(2));

        $output = $this->includeWidget('pdl_rated.inc.php');
        $this->assertStringContainsString('Release1', $output);
        $this->assertStringContainsString('8', $output); // vote = voted/votes = 8/1
    }

    #[Test]
    public function ratedWidgetWithTextTemplate(): void
    {
        global $db_handler, $template;
        $template['rated_row'] = '{name} {text} {vote}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult($this->createReleaseRows(1));

        $output = $this->includeWidget('pdl_rated.inc.php');
        $this->assertStringContainsString('Desc 1', $output);
    }

    #[Test]
    public function ratedWidgetWithShortname(): void
    {
        global $db_handler, $settings;
        $settings['shortname'] = 8;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'VeryLongReleaseName', 'text' => '', 'downloads' => 0,
             'size' => 512, 'votes' => 3, 'voted' => 24, 'views' => 0, 'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_rated.inc.php');
        $this->assertStringContainsString('...', $output);
    }

    #[Test]
    public function ratedWidgetZeroVotes(): void
    {
        global $db_handler;
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'NoVotes', 'text' => '', 'downloads' => 0,
             'size' => 512, 'votes' => 0, 'voted' => 0, 'views' => 0, 'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_rated.inc.php');
        $this->assertStringContainsString('NoVotes', $output);
    }

    #[Test]
    public function ratedWidgetEmptyText(): void
    {
        global $db_handler, $template;
        $template['rated_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => '', 'downloads' => 0,
             'size' => 512, 'votes' => 1, 'voted' => 9, 'views' => 0, 'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_rated.inc.php');
        $this->assertStringContainsString('N/A', $output);
    }

    #[Test]
    public function ratedWidgetTextTruncByChar(): void
    {
        global $db_handler, $settings, $template;
        $settings['trenn_durch'] = 'zeichen';
        $settings['trenn_zeichen'] = 5;
        $template['rated_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => 'A very long text here',
             'downloads' => 0, 'size' => 512, 'votes' => 1, 'voted' => 7, 'views' => 0,
             'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_rated.inc.php');
        $this->assertStringContainsString('...', $output);
    }

    #[Test]
    public function ratedWidgetTextTruncByString(): void
    {
        global $db_handler, $settings, $template;
        $settings['trenn_durch'] = 'string';
        $settings['trenn_string'] = '---';
        $template['rated_row'] = '{name} {text}';
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['release_id' => 1, 'name' => 'App', 'text' => 'Vis---Hid',
             'downloads' => 0, 'size' => 512, 'votes' => 1, 'voted' => 7, 'views' => 0,
             'time' => time(), 'ordner_id' => 0, 'uploader' => 0],
        ]);

        $output = $this->includeWidget('pdl_rated.inc.php');
        $this->assertStringContainsString('Vis', $output);
    }

    // ==================== pdl_config.inc.php ====================

    #[Test]
    public function configSetsDefaultValues(): void
    {
        // Save current globals
        $savedSqlTable = $GLOBALS['sql_table'] ?? null;

        include $this->incDir . 'pdl_config.inc.php';

        $this->assertSame('pdl3', $config_sql_database);
        $this->assertFalse($config_sql_persistent);
        $this->assertSame('MySQL', $config_sql_type);
        $this->assertIsArray($sql_table);
        $this->assertArrayHasKey('comments', $sql_table);
        $this->assertArrayHasKey('files', $sql_table);
        $this->assertArrayHasKey('release', $sql_table);
        $this->assertArrayHasKey('user', $sql_table);
        $this->assertArrayHasKey('ordner', $sql_table);

        // Restore
        if ($savedSqlTable !== null) {
            $GLOBALS['sql_table'] = $savedSqlTable;
        }
    }

    // ==================== pdl_stats.inc.php extended ====================

    #[Test]
    public function statsWidgetShowsFormattedValues(): void
    {
        global $db_handler, $settings;
        $settings['installed'] = time() - 86400 * 10; // 10 days ago
        $db_handler = new MockDbHandler();
        $db_handler->addResult([
            ['file_id' => 1, 'size' => 1048576, 'downloads' => 100, 'mirror' => 0],
        ]);

        $output = $this->includeWidget('pdl_stats.inc.php');
        $this->assertStringContainsString('FILES:1', $output);
        $this->assertStringContainsString('SIZE:', $output);
        $this->assertStringContainsString('DL:100', $output);
    }
}
