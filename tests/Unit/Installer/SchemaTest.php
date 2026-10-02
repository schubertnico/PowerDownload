<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Installer\Schema;
use PowerDownload\Installer\Setup;
use PowerDownload\Installer\Updater;

final class SchemaTest extends InstallerTestCase
{
    /**
     * @return list<string>
     */
    private static function statements(): array
    {
        return Schema::fromFile(self::rootDir() . '/' . Schema::FILENAME);
    }

    #[Test]
    public function schemaIsInstallableAndCreatesExactlyTheConfiguredTables(): void
    {
        $statements = self::statements();
        $configured = Setup::configuredTables(self::rootDir());

        Schema::assertInstallable($statements, $configured);

        $tables = Schema::tableNames($statements);
        sort($tables);
        sort($configured);
        self::assertSame($configured, $tables);
        self::assertCount(15, $tables);
    }

    #[Test]
    public function schemaContainsNoDropNoUserAndNoMysqlOnlyCollation(): void
    {
        $sql = (string) file_get_contents(self::rootDir() . '/' . Schema::FILENAME);

        self::assertStringNotContainsString('utf8mb4_0900_ai_ci', $sql);
        self::assertStringNotContainsString('/*!', $sql);

        foreach (self::statements() as $statement) {
            self::assertDoesNotMatchRegularExpression('/^\s*(DROP|ALTER|DELETE|TRUNCATE|UPDATE)\b/i', $statement);
            self::assertNotSame('pdl3_user', Schema::insertedTable($statement), 'Das Schema darf kein Benutzerkonto enthalten.');
        }
    }

    #[Test]
    public function everyInsertMatchesItsTableAndKeysAreUnique(): void
    {
        $statements = self::statements();
        $columns = [];

        foreach ($statements as $statement) {
            $table = Schema::createdTable($statement);

            if ($table !== null) {
                $columns[$table] = array_keys(Schema::columns($statement));
            }
        }

        $rows = Schema::rows($statements);
        self::assertNotEmpty($rows);

        foreach ($rows as $table => $tableRows) {
            self::assertArrayHasKey($table, $columns, 'INSERT in eine nicht angelegte Tabelle: ' . $table);
            $idColumn = $columns[$table][0];
            $ids = [];

            foreach ($tableRows as $row) {
                self::assertSame([], array_diff(array_keys($row), $columns[$table]), 'Unbekannte Spalte in ' . $table);
                $ids[] = $row[$idColumn] ?? null;
            }

            self::assertSame(count($ids), count(array_unique($ids)), 'Doppelte ID in ' . $table);

            $key = Updater::keyColumn($table);

            if ($key !== null) {
                $keys = array_column($tableRows, $key);
                self::assertSame(count($keys), count(array_unique($keys)), 'Doppelter Schlüssel ' . $key . ' in ' . $table);
            }
        }
    }

    #[Test]
    public function siteUrlSettingExistsInGroupOther(): void
    {
        $settings = Updater::keyRows(Schema::rows(self::statements()))['pdl3_settings'] ?? [];

        self::assertArrayHasKey('site_url', $settings);
        self::assertSame('4', $settings['site_url']['sgroup_id']);
        self::assertSame('Adresse der Download-Seite', $settings['site_url']['name']);
        self::assertSame('', $settings['site_url']['wert']);

        foreach (\PowerDownload\Installer\DatabaseSetup::SETTINGS as $name) {
            self::assertArrayHasKey($name, $settings, 'Der Installer setzt ' . $name . '.');
        }

        self::assertSame('Name der Download-Seite', $settings['sitename']['name']);

        self::assertSame('0', $settings['installed']['wert'], 'installed setzen Installer und update.php.');
    }

    #[Test]
    public function administratorGroupHasAllRights(): void
    {
        $groups = Updater::keyRows(Schema::rows(self::statements()))['pdl3_usergroup'] ?? [];

        self::assertArrayHasKey('2', $groups);
        self::assertSame('Y', $groups['2']['adminaccess']);
        self::assertSame('Y', $groups['2']['settings']);
    }

    #[Test]
    public function defaultsOf350AreComplete(): void
    {
        $rows = Schema::rows(Schema::fromFile(self::rootDir() . '/' . Updater::DEFAULTS_FILE));

        self::assertSame(['pdl3_rights', 'pdl3_settings', 'pdl3_settingsgroup', 'pdl3_template', 'pdl3_templategroup'], array_keys($rows));
        self::assertCount(18, $rows['pdl3_rights']);
        self::assertCount(38, $rows['pdl3_settings']);
        self::assertCount(9, $rows['pdl3_settingsgroup']);
        self::assertCount(20, $rows['pdl3_template']);
        self::assertCount(3, $rows['pdl3_templategroup']);

        $templates = Updater::keyRows($rows)['pdl3_template'];
        self::assertStringStartsWith('<table border="0" cellpadding="5"', (string) $templates['top_box']['wert']);
        self::assertStringContainsString("\n{rows}\n", (string) $templates['top_box']['wert']);
    }

    #[Test]
    public function semicolonsInStringsAndCommentsDoNotSplit(): void
    {
        $sql = "-- Kommentar; mit Semikolon\nSET NAMES utf8mb4;\n"
            . "INSERT INTO `pdl3_template` (`a`, `b`) VALUES ('x&nbsp;y', 'it''s; \\'ok\\'');\n"
            . "/* Block; Kommentar */ CREATE TABLE `pdl3_x` (`a` int NOT NULL DEFAULT '0') ENGINE=InnoDB;";

        $statements = Schema::split($sql);

        self::assertCount(3, $statements);
        self::assertSame('SET NAMES utf8mb4', $statements[0]);
        self::assertSame(['x&nbsp;y', "it's; 'ok'"], Schema::parseInsert($statements[1])['rows'][0]);
    }

    #[Test]
    public function parseInsertHandlesEscapesNumbersNullAndSeveralRows(): void
    {
        $insert = Schema::parseInsert("INSERT INTO `pdl3_t` (`id`, `text`, `n`, `x`) VALUES (1,'a\\nb\\\"c\\\\d\\%',-2.5,NULL),(2,'',0,'é')");

        self::assertSame('pdl3_t', $insert['table']);
        self::assertSame(['id', 'text', 'n', 'x'], $insert['columns']);
        self::assertSame([['1', "a\nb\"c\\d\\%", '-2.5', null], ['2', '', '0', 'é']], $insert['rows']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function brokenInserts(): iterable
    {
        yield 'ohne Spaltenliste' => ["INSERT INTO `pdl3_t` VALUES (1)"];
        yield 'falsche Anzahl' => ["INSERT INTO `pdl3_t` (`a`, `b`) VALUES (1)"];
        yield 'Funktion' => ["INSERT INTO `pdl3_t` (`a`) VALUES (UNIX_TIMESTAMP())"];
        yield 'offen' => ["INSERT INTO `pdl3_t` (`a`) VALUES (1"];
    }

    #[Test]
    #[DataProvider('brokenInserts')]
    public function brokenInsertsAreRejected(string $statement): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Schema::parseInsert($statement);
    }

    #[Test]
    public function columnsOfCreateTable(): void
    {
        $columns = Schema::columns(
            "CREATE TABLE `pdl3_x` (\n  `id` int unsigned NOT NULL AUTO_INCREMENT,\n  `art` enum('a','b,c') NOT NULL DEFAULT 'a',\n"
            . "  `name` varchar(128) NOT NULL DEFAULT '',\n  PRIMARY KEY (`id`),\n  KEY `art` (`art`)\n) ENGINE=InnoDB",
        );

        self::assertSame([
            'id' => 'int unsigned NOT NULL AUTO_INCREMENT',
            'art' => "enum('a','b,c') NOT NULL DEFAULT 'a'",
            'name' => "varchar(128) NOT NULL DEFAULT ''",
        ], $columns);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function rejectedSchemas(): iterable
    {
        $create = "CREATE TABLE `pdl3_a` (`id` int)";

        yield 'DROP' => [[$create, 'DROP TABLE `pdl3_a`']];
        yield 'ALTER' => [[$create, 'ALTER TABLE `pdl3_a` ADD COLUMN `b` int']];
        yield 'fremde Tabelle' => [[$create, 'CREATE TABLE `wp_users` (`id` int)']];
        yield 'INSERT in fremde Tabelle' => [[$create, "INSERT INTO `wp_users` (`id`) VALUES (1)"]];
        yield 'doppelt' => [[$create, $create]];
        yield 'leer' => [[]];
        yield 'Tabelle fehlt' => [["CREATE TABLE `pdl3_b` (`id` int)"]];
    }

    /**
     * @param list<string> $statements
     */
    #[Test]
    #[DataProvider('rejectedSchemas')]
    public function unexpectedStatementsAreRejected(array $statements): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Schema::assertInstallable($statements, ['pdl3_a']);
    }

    #[Test]
    public function missingSchemaFileThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        Schema::fromFile($this->makeRoot() . '/fehlt.sql');
    }
}
