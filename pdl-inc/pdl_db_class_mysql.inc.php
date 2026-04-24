<?php

/**
 * PowerDownload - Database Class (MySQL/MySQLi)
 *
 * @package    PowerDownload
 * @author     PowerScripts
 * @copyright  2001-2002 PowerScripts, 2025 Nico Schubert
 * @license    MIT License
 */

declare(strict_types=1);

class pdl_db_class
{
    public string $config_sql_server = "localhost";
    public string $config_sql_database = "pdl3";
    public string $config_sql_user = "root";
    public string $config_sql_password = "";
    public bool $config_sql_persistent = false;
    public ?mysqli $handler = null;
    public int $querys = 0;

    public function sql_connect(): void
    {
        $host = $this->config_sql_persistent ? 'p:' . $this->config_sql_server : $this->config_sql_server;

        mysqli_report(MYSQLI_REPORT_OFF);

        $connection = @mysqli_connect(
            $host,
            $this->config_sql_user,
            $this->config_sql_password,
            $this->config_sql_database
        );

        if ($connection === false) {
            $error = mysqli_connect_error() ?? 'Unknown error';
            throw new \RuntimeException("Verbindung zum MySQL Server konnte nicht aufgebaut werden: " . $error);
        }

        $this->handler = $connection;

        mysqli_set_charset($this->handler, 'utf8mb4');
    }

    public function sql_query(string $query): mysqli_result|bool
    {
        $this->querys++;
        if ($this->handler === null) {
            return false;
        }
        return mysqli_query($this->handler, $query);
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function sql_fetch_array(mysqli_result|bool|null $result): ?array
    {
        if (!$result instanceof mysqli_result) {
            return null;
        }
        $row = mysqli_fetch_array($result);
        return is_array($row) ? $row : null;
    }

    public function sql_num_rows(mysqli_result|bool|null $result): int
    {
        if (!$result instanceof mysqli_result) {
            return 0;
        }
        return (int) mysqli_num_rows($result);
    }

    public function sql_num_fields(mysqli_result|bool|null $result): int
    {
        if (!$result instanceof mysqli_result) {
            return 0;
        }
        return mysqli_num_fields($result);
    }

    public function sql_escape_string(string $string): string
    {
        if ($this->handler === null) {
            return addslashes($string);
        }
        return mysqli_real_escape_string($this->handler, $string);
    }

    public function sql_escape_int(mixed $value): int
    {
        return (int) $value;
    }

    public function sql_insert_id(): int|string
    {
        if ($this->handler === null) {
            return 0;
        }
        return mysqli_insert_id($this->handler);
    }

    public function sql_close(): void
    {
        if ($this->handler instanceof mysqli) {
            mysqli_close($this->handler);
            $this->handler = null;
        }
    }
}
