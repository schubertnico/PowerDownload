<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerDownload\Installer\DatabaseSetup;
use PowerDownload\Installer\Schema;
use PowerDownload\Installer\Setup;
use PowerDownload\Installer\Updater;

require_once dirname(__DIR__, 2) . '/pdl-inc/installer/autoload.php';

/**
 * Installer und update.php gegen einen echten MySQL- bzw. MariaDB-Server.
 *
 * Läuft nur mit PDL_TEST_DB_HOST, PDL_TEST_DB_USER und PDL_TEST_DB_PASS
 * (optional PDL_TEST_DB_PORT) und dem Recht CREATE DATABASE. Der Test legt
 * eine eigene Datenbank pdl_installer_test_<Zufall> an und entfernt sie
 * danach wieder; andere Datenbanken fasst er nicht an. Beispiel im
 * Aufnahme-Stack:
 *
 *   docker exec -e PDL_TEST_DB_HOST=db -e PDL_TEST_DB_USER=root -e PDL_TEST_DB_PASS=root \
 *     pdl_video_web php vendor/bin/phpunit tests/Integration/InstallerDatabaseTest.php
 */
final class InstallerDatabaseTest extends TestCase
{
    private ?\mysqli $mysqli = null;

    private string $database = '';

    protected function setUp(): void
    {
        $host = getenv('PDL_TEST_DB_HOST');
        $user = getenv('PDL_TEST_DB_USER');
        $password = getenv('PDL_TEST_DB_PASS');

        if (!is_string($host) || $host === '' || !is_string($user) || $user === '' || !is_string($password) || !extension_loaded('mysqli')) {
            self::markTestSkipped('PDL_TEST_DB_HOST, PDL_TEST_DB_USER und PDL_TEST_DB_PASS sind nicht gesetzt.');
        }

        $port = (int) (getenv('PDL_TEST_DB_PORT') ?: 3306);
        $this->database = 'pdl_installer_test_' . bin2hex(random_bytes(4));

        try {
            $server = DatabaseSetup::connect(['host' => $host, 'port' => $port, 'user' => $user, 'password' => $password, 'database' => '']);
            $server->query('CREATE DATABASE `' . $this->database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $server->close();
            $this->mysqli = DatabaseSetup::connect(['host' => $host, 'port' => $port, 'user' => $user, 'password' => $password, 'database' => $this->database]);
        } catch (\mysqli_sql_exception $e) {
            self::markTestSkipped('Testdatenbank nicht verfügbar (Fehlercode ' . $e->getCode() . ').');
        }
    }

    protected function tearDown(): void
    {
        if ($this->mysqli !== null) {
            $this->mysqli->query('DROP DATABASE IF EXISTS `' . $this->database . '`');
            $this->mysqli->close();
            $this->mysqli = null;
        }
    }

    private function db(): \mysqli
    {
        self::assertNotNull($this->mysqli);

        return $this->mysqli;
    }

    /**
     * @return list<string>
     */
    private static function statements(): array
    {
        return Schema::fromFile(dirname(__DIR__, 2) . '/' . Schema::FILENAME);
    }

    /**
     * @return list<string>
     */
    private static function tables(): array
    {
        return Setup::configuredTables(dirname(__DIR__, 2));
    }

    private function install(): int
    {
        return DatabaseSetup::install(
            $this->db(),
            self::statements(),
            self::tables(),
            ['name' => 'Downloads Fotoclub Lichtblick', 'url' => 'https://www.example.org/downloads', 'email' => 'downloads@example.org', 'description' => 'Test'],
            ['nick' => 'Sabine', 'email' => 'admin@example.org', 'password_hash' => password_hash('Lichtblick2026', PASSWORD_DEFAULT)],
            1_790_000_000,
        );
    }

    private function value(string $sql): ?string
    {
        $result = $this->db()->query($sql);
        $row = $result instanceof \mysqli_result ? $result->fetch_row() : null;

        return is_array($row) && $row[0] !== null ? (string) $row[0] : null;
    }

    private function updater(): Updater
    {
        return Updater::fromFiles(dirname(__DIR__, 2), self::tables());
    }

    #[Test]
    public function installCreatesTablesSettingsAndAdministrator(): void
    {
        $adminId = $this->install();

        self::assertCount(count(self::tables()), DatabaseSetup::existingTables($this->db()));
        self::assertSame('2', $this->value('SELECT ugroup_id FROM pdl3_user WHERE user_id = ' . $adminId));
        self::assertTrue(password_verify('Lichtblick2026', (string) $this->value('SELECT passwort FROM pdl3_user WHERE nick = \'Sabine\'')));
        self::assertSame('Downloads Fotoclub Lichtblick', $this->value("SELECT wert FROM pdl3_settings WHERE variablenname = 'sitename'"));
        self::assertSame('Downloads Fotoclub Lichtblick', $this->value("SELECT wert FROM pdl3_settings WHERE variablenname = 'mail_fromname'"));
        self::assertSame('https://www.example.org/downloads', $this->value("SELECT wert FROM pdl3_settings WHERE variablenname = 'site_url'"));
        self::assertSame('1790000000', $this->value("SELECT wert FROM pdl3_settings WHERE variablenname = 'installed'"));
        self::assertStringNotContainsString('\\"', (string) $this->value("SELECT wert FROM pdl3_template WHERE variablenname = 'stats'"), 'Vorlagen ohne verdoppelte Backslashes.');
        self::assertTrue(DatabaseSetup::hasSettingsRows($this->db()));
    }

    #[Test]
    public function freshInstallationNeedsNoUpdate(): void
    {
        $this->install();

        $plan = $this->updater()->plan(Updater::snapshot($this->db()), time());

        self::assertSame([], array_column($plan['actions'], 'label'), 'Schema und frisch installierte Datenbank stimmen überein (Typen, Vorgabewerte, Daten).');
        self::assertSame([], $plan['notes']);
    }

    #[Test]
    public function failedInstallRemovesTheTablesOfThisRun(): void
    {
        $statements = self::statements();
        $statements[] = "INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `wert`) VALUES (1,'doppelt','')";

        try {
            DatabaseSetup::install($this->db(), $statements, self::tables(), ['name' => 'x', 'url' => 'https://example.org', 'email' => 'a@example.org', 'description' => ''], ['nick' => 'abc', 'email' => 'a@example.org', 'password_hash' => 'x'], 1);
            self::fail('Der doppelte Schlüssel muss die Installation abbrechen.');
        } catch (\mysqli_sql_exception $e) {
            self::assertSame(1062, $e->getCode());
        }

        self::assertSame([], DatabaseSetup::existingTables($this->db()));
    }

    #[Test]
    public function updateBrings350DatabaseToTheCurrentStateAndKeepsData(): void
    {
        $this->install();
        $db = $this->db();

        // Zustand 3.5.0 nachstellen
        $db->query("DELETE FROM pdl3_settings WHERE variablenname IN ('site_url', 'sitename', 'guest_group_id')");
        $db->query("UPDATE pdl3_settings SET wert = '0' WHERE variablenname = 'installed'");
        $db->query("UPDATE pdl3_settings SET wert = 'Eigener Absender' WHERE variablenname = 'mail_fromname'");
        $db->query('DELETE FROM pdl3_usergroup WHERE ugroup_id = 3');
        $db->query("UPDATE pdl3_usergroup SET name = 'Gast', vote = 'N', addcomments = 'Y', download = 'Y', adminaccess = 'N' WHERE ugroup_id = 1");
        $db->query("ALTER TABLE pdl3_user ALTER COLUMN get_letter SET DEFAULT 'Y'");
        $db->query("ALTER TABLE pdl3_iplock MODIFY COLUMN art enum('comment','vote','login','register','lostpw') NOT NULL DEFAULT 'comment'");
        $db->query("DELETE FROM pdl3_template WHERE variablenname LIKE 'mail\\_%'");
        $db->query('DELETE FROM pdl3_templategroup WHERE tgroup_id = 9');
        $db->query("INSERT INTO pdl3_templategroup (tgroup_id, name, reihenfolge) VALUES (9, 'Eigene Gruppe', 9)");

        $defaults = Updater::keyRows(Schema::rows(Schema::fromFile(dirname(__DIR__, 2) . '/' . Updater::DEFAULTS_FILE)));
        $stmt = $db->prepare('UPDATE pdl3_template SET wert = ? WHERE variablenname = ?');
        self::assertNotFalse($stmt);

        foreach ($defaults['pdl3_template'] as $name => $row) {
            $wert = str_replace("\n", "\r\n", (string) $row['wert']);
            $key = (string) $name;
            $stmt->bind_param('ss', $wert, $key);
            $stmt->execute();
        }

        $custom = 'Eigene Vorlage';
        $key = 'top_box';
        $stmt->bind_param('ss', $custom, $key);
        $stmt->execute();
        $stmt->close();

        $db->query("INSERT INTO pdl3_iplock (ip, time, file_id, user_id, art) VALUES ('127.0.0.1', 1, 0, 0, 'lostpw')");

        $plan = $this->updater()->plan(Updater::snapshot($db), 1_790_000_123);
        $log = Updater::apply($db, $plan);

        self::assertNotSame([], $log);
        self::assertSame([], array_values(array_filter($log, static fn (array $entry): bool => !$entry['ok'])), 'Alle Aktionen gelingen.');

        self::assertSame('1790000123', $this->value("SELECT wert FROM pdl3_settings WHERE variablenname = 'installed'"));
        self::assertSame('Eigener Absender', $this->value("SELECT wert FROM pdl3_settings WHERE variablenname = 'mail_fromname'"), 'Einstellungswerte bleiben.');
        self::assertSame('', $this->value("SELECT wert FROM pdl3_settings WHERE variablenname = 'site_url'"));
        self::assertSame('Mitglied', $this->value('SELECT name FROM pdl3_usergroup WHERE ugroup_id = 1'));
        self::assertSame('Y', $this->value('SELECT vote FROM pdl3_usergroup WHERE ugroup_id = 1'));
        self::assertSame('Gast', $this->value('SELECT name FROM pdl3_usergroup WHERE ugroup_id = 3'));
        self::assertSame('3', $this->value("SELECT wert FROM pdl3_settings WHERE variablenname = 'guest_group_id'"));
        self::assertSame('Eigene Gruppe', $this->value('SELECT name FROM pdl3_templategroup WHERE tgroup_id = 9'));
        $mailGroup = $this->value("SELECT tgroup_id FROM pdl3_templategroup WHERE name = 'E-Mails'");
        self::assertNotNull($mailGroup);
        self::assertNotSame('9', $mailGroup);
        self::assertSame($mailGroup, $this->value("SELECT tgroup_id FROM pdl3_template WHERE variablenname = 'mail_register'"));
        self::assertSame('Eigene Vorlage', $this->value("SELECT wert FROM pdl3_template WHERE variablenname = 'top_box'"), 'Eigene Vorlagen bleiben.');
        self::assertSame('lostpw', $this->value("SELECT art FROM pdl3_iplock WHERE ip = '127.0.0.1'"), 'ENUM-Werte der Datenbank bleiben.');
        self::assertStringContainsString("'download'", (string) $this->value("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdl3_iplock' AND COLUMN_NAME = 'art'"));
        self::assertSame('N', Updater::dbDefault($this->value("SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdl3_user' AND COLUMN_NAME = 'get_letter'")));

        $second = $this->updater()->plan(Updater::snapshot($db), time());
        self::assertSame([], array_column($second['actions'], 'label'), 'Zweiter Lauf: nichts zu tun.');
        self::assertSame(['top_box'], array_values(array_unique(array_column(array_filter($second['kept'], static fn (array $k): bool => $k['column'] === 'wert'), 'key'))));
    }
}
