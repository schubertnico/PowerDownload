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

        $this->handler = @mysqli_connect(
            $host,
            $this->config_sql_user,
            $this->config_sql_password,
            $this->config_sql_database
        );

        if ($this->handler === false || $this->handler === null) {
            die("Verbindung zum MySQL Server konnte nicht aufgebaut werden. Überprüfen sie die Zugangsdaten zum MySQL Server.");
        }

        // Set charset to UTF-8
        mysqli_set_charset($this->handler, 'utf8mb4');
    }

    public function sql_query(string $query): mysqli_result|bool
    {
        $this->querys++;
        return @mysqli_query($this->handler, $query);
    }

    public function sql_fetch_array(mysqli_result|bool|null $result): ?array
    {
        if ($result === false || $result === null) {
            return null;
        }
        $row = @mysqli_fetch_array($result);
        return $row === false ? null : $row;
    }

    public function sql_num_rows(mysqli_result|bool|null $result): int
    {
        if ($result === false || $result === null) {
            return 0;
        }
        return @mysqli_num_rows($result);
    }

    public function sql_num_fields(mysqli_result|bool|null $result): int
    {
        if ($result === false || $result === null) {
            return 0;
        }
        return @mysqli_num_fields($result);
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
        if ($this->handler !== null) {
            @mysqli_close($this->handler);
            $this->handler = null;
        }
    }
}
