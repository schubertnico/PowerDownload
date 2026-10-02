<?php

/**
 * PowerDownload - Datenbankzugriffe des Web-Installers
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

/**
 * Verbindungstest, Prüfung auf bestehende Tabellen und das Einspielen von
 * Schema, Einstellungen und Administrator.
 *
 * @phpstan-import-type DbInput from FormValidator
 * @phpstan-import-type WebsiteSettings from FormValidator
 *
 * @phpstan-type AdminData array{nick: string, email: string, password_hash: string}
 */
final class DatabaseSetup
{
    public const int CONNECT_TIMEOUT = 5;

    /**
     * Gruppe „Administrator“ aus pdl3_schema.sql (alle Rechte).
     */
    public const int ADMIN_GROUP = 2;

    /**
     * Einstellungen, die der Installer setzt.
     */
    public const array SETTINGS = ['sitename', 'mail_fromname', 'mail_fromaddr', 'site_description', 'site_url', 'installed'];

    /**
     * Baut eine eigene Verbindung, damit der Installer beliebige Zugangsdaten
     * prüfen kann. Wirft mysqli_sql_exception bei Fehlern.
     *
     * @param DbInput $db
     */
    public static function connect(#[\SensitiveParameter] array $db): \mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $mysqli = mysqli_init();

        if ($mysqli === false) {
            throw new \mysqli_sql_exception('mysqli_init() ist fehlgeschlagen.');
        }

        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, self::CONNECT_TIMEOUT);

        // Warnungen wie „getaddrinfo failed“ nicht ausgeben, der Fehler kommt als Exception.
        set_error_handler(static fn (): bool => true);

        try {
            $mysqli->real_connect($db['host'], $db['user'], $db['password'], $db['database'], $db['port']);
        } finally {
            restore_error_handler();
        }

        $mysqli->set_charset('utf8mb4');

        return $mysqli;
    }

    /**
     * Verbindungstest für Schritt 2: Serverversion und vorhandene pdl3_-Tabellen.
     *
     * @param \Closure(DbInput): \mysqli $connect
     * @param DbInput $db
     *
     * @return array{ok: bool, code: int, server: ServerVersion|null, tables: list<string>}
     *                                                                                     code = MySQL-Fehlernummer, wenn ok false ist
     */
    public static function inspect(\Closure $connect, #[\SensitiveParameter] array $db): array
    {
        try {
            $mysqli = $connect($db);
            $server = ServerVersion::parse(self::serverVersion($mysqli));
            $tables = self::existingTables($mysqli);
            $mysqli->close();
        } catch (\mysqli_sql_exception $e) {
            return ['ok' => false, 'code' => $e->getCode(), 'server' => null, 'tables' => []];
        }

        return ['ok' => true, 'code' => 0, 'server' => $server, 'tables' => $tables];
    }

    /**
     * Versionskennung des Servers, z. B. „8.0.46“ oder „10.11.15-MariaDB“.
     */
    public static function serverVersion(\mysqli $mysqli): string
    {
        $result = $mysqli->query('SELECT VERSION()');
        $row = $result instanceof \mysqli_result ? $result->fetch_row() : null;
        $version = is_array($row) ? ($row[0] ?? null) : null;

        return is_string($version) ? $version : $mysqli->server_info;
    }

    /**
     * Vorhandene Tabellen mit dem Präfix pdl3_.
     *
     * @return list<string>
     */
    public static function existingTables(\mysqli $mysqli): array
    {
        $result = $mysqli->query("SHOW TABLES LIKE 'pdl3\\_%'");

        if (!$result instanceof \mysqli_result) {
            return [];
        }

        $tables = [];

        while (is_array($row = $result->fetch_row())) {
            $table = $row[0] ?? null;

            if (is_string($table)) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    /**
     * Ist PowerDownload in dieser Datenbank eingerichtet?
     *
     * @return bool|null true = pdl3_settings hat mindestens eine Zeile,
     *                   false = Tabelle fehlt oder ist leer,
     *                   null = unklar (z. B. fehlende Rechte)
     */
    public static function hasSettingsRows(\mysqli $mysqli): ?bool
    {
        try {
            $result = $mysqli->query('SELECT COUNT(*) FROM pdl3_settings');
        } catch (\mysqli_sql_exception $e) {
            return $e->getCode() === 1146 ? false : null;
        }

        $row = $result instanceof \mysqli_result ? $result->fetch_row() : null;

        return is_array($row) && (int) ($row[0] ?? 0) > 0;
    }

    /**
     * Spielt Schema, Einstellungen und Administrator ein.
     *
     * Schlägt ein Schritt fehl, werden die in diesem Lauf angelegten Tabellen
     * wieder entfernt, damit ein erneuter Versuch auf einer leeren Datenbank
     * beginnt. Bereits vorhandene Tabellen werden nie angefasst; deshalb muss
     * der Aufrufer vorher prüfen, dass keine pdl3_-Tabellen existieren.
     *
     * @param list<string> $statements
     * @param list<string> $requiredTables
     * @param WebsiteSettings $website
     * @param AdminData $admin
     *
     * @throws \mysqli_sql_exception bei Datenbankfehlern (angelegte Tabellen sind dann entfernt)
     * @throws \UnexpectedValueException bei einem unvollständigen oder veränderten Schema
     *
     * @return int ID des Administrators
     */
    public static function install(
        \mysqli $mysqli,
        array $statements,
        array $requiredTables,
        array $website,
        #[\SensitiveParameter]
        array $admin,
        int $now,
    ): int {
        Schema::assertInstallable($statements, $requiredTables);

        $created = [];

        try {
            foreach ($statements as $statement) {
                $mysqli->query($statement);
                $table = Schema::createdTable($statement);

                if ($table !== null) {
                    $created[] = $table;
                }
            }

            self::saveWebsite($mysqli, $website, $now);

            return self::createAdmin($mysqli, $admin, $now);
        } catch (\mysqli_sql_exception|\UnexpectedValueException $e) {
            self::dropTables($mysqli, $created);

            throw $e;
        }
    }

    /**
     * Verständliche Fehlermeldung zu einer MySQL-Fehlernummer, ohne
     * Zugangsdaten. Die Originalmeldung des Servers nennt u. a. Benutzer und
     * Host und wird deshalb nie angezeigt.
     */
    public static function friendlyError(int $code): string
    {
        return match ($code) {
            1045, 1698 => 'Die Anmeldung am Datenbankserver ist fehlgeschlagen: Benutzername oder Passwort ist falsch.',
            1044 => 'Der Benutzer hat keine Berechtigung für diese Datenbank.',
            1049 => 'Die Datenbank existiert nicht. Bitte legen Sie sie zuerst an (z. B. im Kundenmenü Ihres Hosters) oder prüfen Sie den Namen.',
            1130 => 'Der Datenbankserver lässt keine Verbindungen von diesem Webserver zu.',
            2002, 2003 => 'Der Datenbankserver ist nicht erreichbar. Bitte prüfen Sie Server und Port.',
            2005 => 'Der Datenbankserver ist unbekannt. Bitte prüfen Sie die Schreibweise des Servernamens.',
            2006, 2013 => 'Die Verbindung zum Datenbankserver wurde unterbrochen. Bitte versuchen Sie es erneut.',
            1142, 1227 => 'Dem Datenbank-Benutzer fehlen Rechte (benötigt werden CREATE, DROP, SELECT, INSERT, UPDATE, DELETE, INDEX und ALTER).',
            1050 => 'In der Datenbank gibt es bereits eine PowerDownload-Tabelle. Es wurde nichts überschrieben.',
            1062 => 'Ein Eintrag ist bereits vorhanden (doppelter Schlüssel).',
            1273 => 'Der Datenbankserver kennt die Sortierung utf8mb4_unicode_ci nicht. Bitte prüfen Sie die Version des Servers.',
            1366 => 'Eine Eingabe enthält Zeichen, die die Datenbank nicht speichern kann.',
            0 => 'Die Datenbank ist nicht erreichbar oder hat die Anfrage abgelehnt.',
            default => sprintf('Die Datenbank hat die Anfrage abgelehnt (Fehlercode %d).', $code),
        };
    }

    /**
     * Name (sitename und Absendername), Adresse (site_url, Grundlage der Links
     * in E-Mails), Absender, Kurzbeschreibung und Installationszeitpunkt.
     *
     * @param WebsiteSettings $website
     */
    private static function saveWebsite(\mysqli $mysqli, array $website, int $now): void
    {
        $result = $mysqli->query("SELECT variablenname FROM pdl3_settings WHERE variablenname IN ('" . implode("', '", self::SETTINGS) . "')");
        $found = [];

        while ($result instanceof \mysqli_result && is_array($row = $result->fetch_row())) {
            $found[] = (string) ($row[0] ?? '');
        }

        $missing = array_diff(self::SETTINGS, $found);

        if ($missing !== []) {
            throw new \UnexpectedValueException('Der Schemadatei fehlen die Einstellungen ' . implode(', ', $missing) . '. Bitte laden Sie pdl-inc/pdl3_schema.sql erneut hoch.');
        }

        $values = [
            'sitename' => $website['name'],
            'mail_fromname' => $website['name'],
            'mail_fromaddr' => $website['email'],
            'site_description' => $website['description'],
            'site_url' => $website['url'],
            'installed' => (string) $now,
        ];

        $stmt = self::prepare($mysqli, 'UPDATE pdl3_settings SET wert = ? WHERE variablenname = ?');

        foreach ($values as $name => $value) {
            $stmt->bind_param('ss', $value, $name);
            $stmt->execute();
        }

        $stmt->close();
    }

    /**
     * Legt den Administrator in der Gruppe „Administrator“ an. Das Passwort
     * liegt nur als Hash vor (password_hash() mit PASSWORD_DEFAULT; die
     * Anmeldung prüft mit password_verify()).
     *
     * @param AdminData $admin
     */
    private static function createAdmin(\mysqli $mysqli, #[\SensitiveParameter] array $admin, int $now): int
    {
        $result = $mysqli->query('SELECT adminaccess FROM pdl3_usergroup WHERE ugroup_id = ' . self::ADMIN_GROUP);
        $row = $result instanceof \mysqli_result ? $result->fetch_row() : null;

        if (!is_array($row) || ($row[0] ?? '') !== 'Y') {
            throw new \UnexpectedValueException('In der Schemadatei fehlt die Benutzergruppe „Administrator“ mit Admin-Zugang. Bitte laden Sie pdl-inc/pdl3_schema.sql erneut hoch.');
        }

        $stmt = self::prepare(
            $mysqli,
            'INSERT INTO pdl3_user (nick, email, passwort, ugroup_id, homepage, get_letter, signatur, remind_code, remind_expires, lastactive, session_token)'
            . " VALUES (?, ?, ?, ?, '', 'N', '', '', 0, ?, '')",
        );
        $group = self::ADMIN_GROUP;
        $stmt->bind_param('sssii', $admin['nick'], $admin['email'], $admin['password_hash'], $group, $now);
        $stmt->execute();
        $stmt->close();

        $adminId = (int) $mysqli->insert_id;

        if ($adminId <= 0) {
            throw new \UnexpectedValueException('Das Administrator-Konto konnte nicht angelegt werden.');
        }

        return $adminId;
    }

    /**
     * Vorbereitete Anweisung; mysqli_report(STRICT) wirft bei Fehlern bereits
     * selbst, die Prüfung auf false ist nur die Absicherung dafür.
     */
    private static function prepare(\mysqli $mysqli, string $sql): \mysqli_stmt
    {
        $stmt = $mysqli->prepare($sql);

        if ($stmt === false) {
            throw new \mysqli_sql_exception('Die Anweisung konnte nicht vorbereitet werden.', $mysqli->errno);
        }

        return $stmt;
    }

    /**
     * @param list<string> $tables in diesem Lauf angelegte Tabellen
     */
    private static function dropTables(\mysqli $mysqli, array $tables): void
    {
        foreach (array_reverse($tables) as $table) {
            if (preg_match('/^' . Schema::TABLE_PREFIX . '[a-z0-9_]+$/', $table) !== 1) {
                continue;
            }

            try {
                $mysqli->query('DROP TABLE IF EXISTS `' . $table . '`');
            } catch (\mysqli_sql_exception $e) {
                // Aufräumen ist bestmöglich; der ursprüngliche Fehler zählt.
                error_log('PowerDownload-Installer: Tabelle ' . $table . ' konnte nicht entfernt werden (Fehlercode ' . $e->getCode() . ').');
            }
        }
    }
}
