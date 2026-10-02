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
    public int $config_sql_port = 3306;
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
            $this->config_sql_database,
            $this->config_sql_port
        );

        if ($connection === false) {
            $error = mysqli_connect_error() ?? 'Unknown error';
            throw new \RuntimeException("Verbindung zum MySQL Server konnte nicht aufgebaut werden: " . $error);
        }

        $this->handler = $connection;

        mysqli_set_charset($this->handler, 'utf8mb4');
    }

    /** Fehlertext der zuletzt fehlgeschlagenen Abfrage ('' = kein Fehler). */
    private string $last_error = '';

    /** MySQL-Fehlernummer der zuletzt fehlgeschlagenen Abfrage (0 = kein Fehler). */
    private int $last_errno = 0;

    /**
     * Führt eine Abfrage aus. Schlägt sie fehl, landet der MySQL-Fehler samt
     * gekürzter Abfrage (ohne Passwörter und Tokens) per error_log() im
     * PHP-Fehlerprotokoll; sql_error()/sql_errno() liefern ihn danach.
     */
    public function sql_query(string $query): mysqli_result|bool
    {
        $this->querys++;
        if ($this->handler === null) {
            $this->last_errno = -1;
            $this->last_error = 'Keine Verbindung zur Datenbank.';
            return false;
        }
        $result = mysqli_query($this->handler, $query);
        if ($result === false) {
            $this->last_errno = mysqli_errno($this->handler);
            $this->last_error = mysqli_error($this->handler);
            error_log(
                'PowerDownload SQL-Fehler ' . $this->last_errno . ': ' . $this->last_error
                . ' | Abfrage: ' . self::sql_log_excerpt($query)
            );
        } else {
            $this->last_errno = 0;
            $this->last_error = '';
        }
        return $result;
    }

    /**
     * Fehlertext der letzten Abfrage oder '' wenn sie erfolgreich war.
     */
    public function sql_error(): string
    {
        return $this->last_error;
    }

    /**
     * MySQL-Fehlernummer der letzten Abfrage oder 0 wenn sie erfolgreich war.
     */
    public function sql_errno(): int
    {
        return $this->last_errno;
    }

    /**
     * Kürzt eine Abfrage für das Fehlerprotokoll. Enthält sie Hinweise auf
     * Passwörter, Tokens oder Wiederherstellungscodes, werden alle
     * String-Literale durch '***' ersetzt.
     */
    public static function sql_log_excerpt(string $query, int $max = 300): string
    {
        $excerpt = (string) preg_replace('/\s+/', ' ', trim($query));
        if (preg_match('/pass|passwort|password|token|remind|secret|ftp_/i', $excerpt) === 1) {
            $excerpt = (string) preg_replace("/'(?:[^'\\\\]|\\\\.)*'/s", "'***'", $excerpt);
        }
        if (strlen($excerpt) > $max) {
            $excerpt = mb_strcut($excerpt, 0, $max, 'UTF-8') . ' …';
        }
        return $excerpt;
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
