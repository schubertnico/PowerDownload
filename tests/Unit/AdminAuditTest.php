<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Tests\Support\MockDbHandler;

/**
 * Tests für pdl-inc/pdl_admin_audit.inc.php
 */
class AdminAuditTest extends TestCase
{
    private array $sqlTable;
    private \ReflectionClass $dbReflection;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/pdl-inc/pdl_admin_audit.inc.php';
        $this->sqlTable = ['admin_log' => 'pdl3_admin_log'];
    }

    #[Test]
    public function ensureTableIssuesCreateTable(): void
    {
        $db = new class extends MockDbHandler {
            public array $sentQueries = [];
            public function sql_query(string $query): \PowerDownload\Tests\Support\MockResult
            {
                $this->sentQueries[] = $query;
                return parent::sql_query($query);
            }
        };
        pdl_audit_ensure_table($db, $this->sqlTable);
        $this->assertNotEmpty($db->sentQueries);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS', $db->sentQueries[0]);
        $this->assertStringContainsString('pdl3_admin_log', $db->sentQueries[0]);
    }

    #[Test]
    public function auditLogWritesInsertWithUserAction(): void
    {
        $db = new class extends MockDbHandler {
            public array $sentQueries = [];
            public function sql_query(string $query): \PowerDownload\Tests\Support\MockResult
            {
                $this->sentQueries[] = $query;
                return parent::sql_query($query);
            }
        };

        pdl_audit_log(
            $db,
            $this->sqlTable,
            ['user_id' => 42],
            'create',
            'release',
            123,
            '10.0.0.1'
        );

        $insertQuery = end($db->sentQueries);
        $this->assertStringContainsString('INSERT INTO', $insertQuery);
        $this->assertStringContainsString('pdl3_admin_log', $insertQuery);
        $this->assertStringContainsString("42", $insertQuery);
        $this->assertStringContainsString("'create'", $insertQuery);
        $this->assertStringContainsString("'release'", $insertQuery);
        $this->assertStringContainsString("123", $insertQuery);
        $this->assertStringContainsString("'10.0.0.1'", $insertQuery);
    }

    #[Test]
    public function auditLogHandlesNullUserDetails(): void
    {
        $db = new class extends MockDbHandler {
            public array $sentQueries = [];
            public function sql_query(string $query): \PowerDownload\Tests\Support\MockResult
            {
                $this->sentQueries[] = $query;
                return parent::sql_query($query);
            }
        };

        pdl_audit_log(
            $db,
            $this->sqlTable,
            null,
            'delete',
            'ordner',
            1,
            ''
        );
        $insert = end($db->sentQueries);
        $this->assertStringContainsString("INSERT INTO", $insert);
        // user_id defaults to 0
        $this->assertMatchesRegularExpression('/VALUES\s*\(\s*0,/', $insert);
    }

    #[Test]
    public function auditRecentLimitsResults(): void
    {
        $db = new MockDbHandler();
        // first query: CREATE TABLE -> empty
        // second: SELECT -> we mock empty
        $db->addResult([]); // CREATE TABLE
        $db->addResult([
            ['log_id' => 2, 'user_id' => 1, 'action' => 'create', 'target_type' => 'release', 'target_id' => 10, 'time' => 1700000001, 'ip' => '1.1.1.1'],
            ['log_id' => 1, 'user_id' => 1, 'action' => 'delete', 'target_type' => 'release', 'target_id' => 9, 'time' => 1700000000, 'ip' => '1.1.1.1'],
        ]);
        $rows = pdl_audit_recent($db, $this->sqlTable, 50);
        $this->assertCount(2, $rows);
        $this->assertSame('create', $rows[0]['action']);
    }
}
