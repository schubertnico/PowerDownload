<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Tests\Support\MockDbHandler;

/**
 * Tests für treeview_select() mit Static-Cache und $selected-Parameter.
 */
class TreeviewSelectTest extends TestCase
{
    protected function setUp(): void
    {
        // Cache vor jedem Test zurücksetzen
        treeview_select_reset_cache();
    }

    protected function tearDown(): void
    {
        treeview_select_reset_cache();
    }

    #[Test]
    public function emptyTreeProducesEmptyOutput(): void
    {
        global $db_handler, $sql_table;
        $db_handler = new MockDbHandler();
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $db_handler->addResult([]);

        $out = treeview_select(0, '-');
        $this->assertSame('', $out);
    }

    #[Test]
    public function renderTreeStructure(): void
    {
        global $db_handler, $sql_table;
        $db_handler = new MockDbHandler();
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => 'Root1'],
            ['ordner_id' => 2, 'sordner_id' => 1, 'name' => 'Child1'],
            ['ordner_id' => 3, 'sordner_id' => 0, 'name' => 'Root2'],
        ]);

        $out = treeview_select(0, '-');
        $this->assertStringContainsString('value="1"', $out);
        $this->assertStringContainsString('Root1', $out);
        $this->assertStringContainsString('value="2"', $out);
        $this->assertStringContainsString('--Child1', $out); // nested level
        $this->assertStringContainsString('value="3"', $out);
        $this->assertStringContainsString('Root2', $out);
    }

    #[Test]
    public function selectedAttributeIsRenderedOnMatchingId(): void
    {
        global $db_handler, $sql_table;
        $db_handler = new MockDbHandler();
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => 'A'],
            ['ordner_id' => 2, 'sordner_id' => 0, 'name' => 'B'],
        ]);

        $out = treeview_select(0, '-', 2);
        $this->assertStringContainsString('value="2" selected', $out);
        $this->assertStringNotContainsString('value="1" selected', $out);
    }

    #[Test]
    public function namesAreHtmlEscaped(): void
    {
        global $db_handler, $sql_table;
        $db_handler = new MockDbHandler();
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => '<script>alert(1)</script>'],
        ]);

        $out = treeview_select(0, '-');
        $this->assertStringNotContainsString('<script>alert(1)</script>', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);
    }

    #[Test]
    public function cacheIsReusedBetweenCalls(): void
    {
        global $db_handler, $sql_table;
        $db_handler = new MockDbHandler();
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => 'A'],
        ]);

        treeview_select(0, '-');
        treeview_select(0, '-', 1);
        treeview_select(0, '-', 0);

        // Erwartet: nur EINE DB-Query, weil Cache greift
        $this->assertSame(1, $db_handler->querys);
    }

    #[Test]
    public function resetCacheForcesNewQuery(): void
    {
        global $db_handler, $sql_table;
        $db_handler = new MockDbHandler();
        $sql_table = ['ordner' => 'pdl3_ordner'];
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => 'A'],
        ]);
        $db_handler->addResult([
            ['ordner_id' => 1, 'sordner_id' => 0, 'name' => 'A'],
        ]);

        treeview_select(0, '-');
        treeview_select_reset_cache();
        treeview_select(0, '-');

        $this->assertSame(2, $db_handler->querys);
    }
}
