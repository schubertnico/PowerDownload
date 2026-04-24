<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use pdl_db_class;
use RuntimeException;

class DbClassTest extends TestCase
{
    private pdl_db_class $db;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/pdl-inc/pdl_db_class_mysql.inc.php';
        $this->db = new pdl_db_class();
    }

    #[Test]
    public function defaultProperties(): void
    {
        $this->assertSame('localhost', $this->db->config_sql_server);
        $this->assertSame('pdl3', $this->db->config_sql_database);
        $this->assertSame('root', $this->db->config_sql_user);
        $this->assertSame('', $this->db->config_sql_password);
        $this->assertFalse($this->db->config_sql_persistent);
        $this->assertNull($this->db->handler);
        $this->assertSame(0, $this->db->querys);
    }

    #[Test]
    public function sqlConnectThrowsOnInvalidCredentials(): void
    {
        $this->db->config_sql_server = 'invalid_host_that_does_not_exist';
        $this->db->config_sql_database = 'nonexistent';
        $this->db->config_sql_user = 'nobody';
        $this->db->config_sql_password = 'wrong';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Verbindung zum MySQL Server/');
        $this->db->sql_connect();
    }

    #[Test]
    public function sqlQueryWithNullHandlerReturnsFalse(): void
    {
        $result = $this->db->sql_query('SELECT 1');
        $this->assertFalse($result);
        $this->assertSame(1, $this->db->querys);
    }

    #[Test]
    public function sqlQueryIncrementsCounter(): void
    {
        $this->db->sql_query('SELECT 1');
        $this->db->sql_query('SELECT 2');
        $this->assertSame(2, $this->db->querys);
    }

    #[Test]
    public function sqlFetchArrayWithFalseReturnsNull(): void
    {
        $this->assertNull($this->db->sql_fetch_array(false));
    }

    #[Test]
    public function sqlFetchArrayWithNullReturnsNull(): void
    {
        $this->assertNull($this->db->sql_fetch_array(null));
    }

    #[Test]
    public function sqlFetchArrayWithBoolReturnsNull(): void
    {
        $this->assertNull($this->db->sql_fetch_array(true));
    }

    #[Test]
    public function sqlNumRowsWithFalseReturnsZero(): void
    {
        $this->assertSame(0, $this->db->sql_num_rows(false));
    }

    #[Test]
    public function sqlNumRowsWithNullReturnsZero(): void
    {
        $this->assertSame(0, $this->db->sql_num_rows(null));
    }

    #[Test]
    public function sqlNumFieldsWithFalseReturnsZero(): void
    {
        $this->assertSame(0, $this->db->sql_num_fields(false));
    }

    #[Test]
    public function sqlNumFieldsWithNullReturnsZero(): void
    {
        $this->assertSame(0, $this->db->sql_num_fields(null));
    }

    #[Test]
    public function sqlEscapeStringWithoutHandlerUsesAddslashes(): void
    {
        $result = $this->db->sql_escape_string("It's a test");
        $this->assertSame("It\\'s a test", $result);
    }

    #[Test]
    public function sqlEscapeStringHandlesQuotes(): void
    {
        $result = $this->db->sql_escape_string('He said "hello"');
        $this->assertSame('He said \\"hello\\"', $result);
    }

    #[Test]
    public function sqlEscapeIntCastsToInt(): void
    {
        $this->assertSame(42, $this->db->sql_escape_int(42));
        $this->assertSame(0, $this->db->sql_escape_int('abc'));
        $this->assertSame(10, $this->db->sql_escape_int('10'));
        $this->assertSame(3, $this->db->sql_escape_int(3.7));
        $this->assertSame(0, $this->db->sql_escape_int(null));
    }

    #[Test]
    public function sqlInsertIdWithNullHandlerReturnsZero(): void
    {
        $this->assertSame(0, $this->db->sql_insert_id());
    }

    #[Test]
    public function sqlCloseWithNullHandlerDoesNothing(): void
    {
        $this->db->sql_close();
        $this->assertNull($this->db->handler);
    }

    #[Test]
    public function sqlConnectWithPersistentFlag(): void
    {
        $this->db->config_sql_persistent = true;
        $this->db->config_sql_server = 'invalid_host_that_does_not_exist';

        $this->expectException(RuntimeException::class);
        $this->db->sql_connect();
    }
}
