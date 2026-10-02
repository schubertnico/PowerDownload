<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\Test;
use PowerDownload\Installer\Schema;
use PowerDownload\Installer\Updater;

/**
 * Abgleich von update.php ohne Datenbank: plan() arbeitet auf Arrays.
 *
 * @phpstan-import-type Snapshot from Updater
 * @phpstan-import-type Plan from Updater
 */
final class UpdaterTest extends InstallerTestCase
{
    /**
     * Kleines Schema für genau vorhersagbare Pläne.
     */
    private const string SCHEMA = <<<'SQL'
        SET NAMES utf8mb4;
        CREATE TABLE `pdl3_iplock` (
          `ip` varchar(45) NOT NULL DEFAULT '',
          `art` enum('comment','vote','login','download') NOT NULL DEFAULT 'comment',
          `time` bigint NOT NULL DEFAULT '0'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        CREATE TABLE `pdl3_settings` (
          `setting_id` int unsigned NOT NULL AUTO_INCREMENT,
          `variablenname` varchar(64) NOT NULL DEFAULT '',
          `name` varchar(128) NOT NULL DEFAULT '',
          `bez` varchar(255) NOT NULL DEFAULT '',
          `wert` text NOT NULL,
          `sgroup_id` int NOT NULL DEFAULT '0',
          PRIMARY KEY (`setting_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `sgroup_id`) VALUES (1,'script_file','Adresse des Skripts','Neue Beschreibung','downloads.php?',4);
        INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `sgroup_id`) VALUES (2,'installed','','','0',0);
        INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `sgroup_id`) VALUES (40,'site_url','Adresse der Download-Seite','Mit https://','',4);
        CREATE TABLE `pdl3_settingsgroup` (
          `sgroup_id` int unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(128) NOT NULL DEFAULT '',
          PRIMARY KEY (`sgroup_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`) VALUES (4,'Sonstiges');
        INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`) VALUES (10,'Neue Gruppe');
        CREATE TABLE `pdl3_template` (
          `template_id` int unsigned NOT NULL AUTO_INCREMENT,
          `variablenname` varchar(64) NOT NULL DEFAULT '',
          `name` varchar(128) NOT NULL DEFAULT '',
          `wert` text NOT NULL,
          PRIMARY KEY (`template_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `wert`) VALUES (1,'top_box','Top-Box','<div class="neu">\n{rows}\n</div>');
        INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `wert`) VALUES (2,'flop_box','Flop-Box','<div class="neu">\n{rows}\n</div>');
        INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `wert`) VALUES (3,'same_box','Gleich','<b>gleich</b>');
        CREATE TABLE `pdl3_usergroup` (
          `ugroup_id` int unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(128) NOT NULL DEFAULT '',
          `adminaccess` enum('Y','N') NOT NULL DEFAULT 'N',
          `newright` enum('Y','N') NOT NULL DEFAULT 'N',
          PRIMARY KEY (`ugroup_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `adminaccess`, `newright`) VALUES (1,'Gast','N','N');
        INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `adminaccess`, `newright`) VALUES (2,'Administrator','Y','Y');
        CREATE TABLE `pdl3_comments` (
          `comment_id` int unsigned NOT NULL AUTO_INCREMENT,
          `text` text NOT NULL,
          PRIMARY KEY (`comment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;

    /**
     * Grunddaten „3.5.0“ des kleinen Schemas.
     */
    private const string DEFAULTS = <<<'SQL'
        INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `bez`, `wert`, `sgroup_id`) VALUES (1,'script_file','Script-URL','Alte Beschreibung','downloads.php?',4),(2,'installed','','','0',0);
        INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`) VALUES (4,'Sonstiges');
        INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `wert`) VALUES (1,'top_box','Top-Box','<table>\n{rows}\n</table>'),(2,'flop_box','Flop-Box','<table>\n{rows}\n</table>'),(3,'same_box','Gleich','<b>gleich</b>');
        SQL;

    private static function updater(string $schema = self::SCHEMA, string $defaults = self::DEFAULTS): Updater
    {
        return new Updater(Schema::split($schema), Updater::keyRows(Schema::rows(Schema::split($defaults))));
    }

    /**
     * Datenbankstand „3.5.0“ des kleinen Schemas, wie ihn snapshot() liefern würde.
     *
     * @return Snapshot
     */
    private static function snapshot350(): array
    {
        return [
            'tables' => ['pdl3_iplock', 'pdl3_settings', 'pdl3_settingsgroup', 'pdl3_template', 'pdl3_usergroup', 'pdl3_comments', 'pdl3_fremd'],
            'columns' => [
                'pdl3_iplock' => ['ip' => 'varchar(32)', 'art' => "enum('comment','vote','login','lostpw')", 'time' => 'int(11)'],
                'pdl3_settings' => ['setting_id' => 'int unsigned', 'variablenname' => 'varchar(64)', 'name' => 'varchar(128)', 'bez' => 'varchar(255)', 'wert' => 'text', 'sgroup_id' => 'int'],
                'pdl3_settingsgroup' => ['sgroup_id' => 'int unsigned', 'name' => 'varchar(255)'],
                'pdl3_template' => ['template_id' => 'int unsigned', 'variablenname' => 'varchar(64)', 'name' => 'varchar(128)', 'wert' => 'mediumtext'],
                'pdl3_usergroup' => ['ugroup_id' => 'int unsigned', 'name' => 'varchar(128)', 'adminaccess' => "enum('Y','N')"],
                'pdl3_comments' => ['comment_id' => 'int unsigned', 'text' => 'text'],
            ],
            'rows' => [
                'pdl3_settings' => [
                    ['setting_id' => '1', 'variablenname' => 'script_file', 'name' => 'Script-URL', 'bez' => 'Eigene Beschreibung', 'wert' => 'index.php?', 'sgroup_id' => '4'],
                    ['setting_id' => '2', 'variablenname' => 'installed', 'name' => '', 'bez' => '', 'wert' => '0', 'sgroup_id' => '0'],
                    ['setting_id' => '40', 'variablenname' => 'eigene', 'name' => 'Eigene Einstellung', 'bez' => '', 'wert' => 'x', 'sgroup_id' => '4'],
                ],
                'pdl3_settingsgroup' => [['sgroup_id' => '4', 'name' => 'Sonstiges']],
                'pdl3_template' => [
                    // Im Adminbereich gespeichert: CRLF statt LF, sonst unverändert
                    ['template_id' => '1', 'variablenname' => 'top_box', 'name' => 'Top-Box', 'wert' => "<table>\r\n{rows}\r\n</table>"],
                    ['template_id' => '2', 'variablenname' => 'flop_box', 'name' => 'Flop-Box', 'wert' => "<table class=\"meins\">\n{rows}\n</table>"],
                    ['template_id' => '3', 'variablenname' => 'same_box', 'name' => 'Gleich', 'wert' => '<b>gleich</b>'],
                ],
                'pdl3_usergroup' => [
                    ['ugroup_id' => '1', 'name' => 'Gast', 'adminaccess' => 'N'],
                    ['ugroup_id' => '2', 'name' => 'Admins', 'adminaccess' => 'Y'],
                    ['ugroup_id' => '3', 'name' => 'Eigene Gruppe', 'adminaccess' => 'N'],
                ],
            ],
        ];
    }

    /**
     * @param Plan $plan
     *
     * @return list<string>
     */
    private static function sqlOf(array $plan): array
    {
        $sql = [];

        foreach ($plan['actions'] as $action) {
            foreach ($action['queries'] as $query) {
                $sql[] = $query['sql'] . ($query['params'] === [] ? '' : ' | ' . implode(' | ', array_map(static fn (?string $p): string => $p ?? 'NULL', $query['params'])));
            }
        }

        return $sql;
    }

    #[Test]
    public function planFor350DatabaseIsExact(): void
    {
        $plan = self::updater()->plan(self::snapshot350(), 1_790_000_000);

        self::assertSame([
            // b) neue Spalte hinter der vorigen Schemaspalte
            "ALTER TABLE `pdl3_usergroup` ADD COLUMN `newright` enum('Y','N') NOT NULL DEFAULT 'N' AFTER `adminaccess`",
            // c) Spaltentypen: ENUM ergänzt (lostpw bleibt), VARCHAR verlängert, INT zu BIGINT.
            "ALTER TABLE `pdl3_iplock` MODIFY COLUMN `ip` varchar(45) NOT NULL DEFAULT ''",
            "ALTER TABLE `pdl3_iplock` MODIFY COLUMN `art` enum('comment','vote','login','download','lostpw') NOT NULL DEFAULT 'comment'",
            "ALTER TABLE `pdl3_iplock` MODIFY COLUMN `time` bigint NOT NULL DEFAULT '0'",
            // b) Werte aus dem Schema für vorhandene Gruppen (Gruppe 3 gibt es im Schema nicht)
            'UPDATE `pdl3_usergroup` SET `newright` = ? WHERE `ugroup_id` = ? | N | 1',
            'UPDATE `pdl3_usergroup` SET `newright` = ? WHERE `ugroup_id` = ? | Y | 2',
            // d) fehlende Datensätze: Gruppe mit ID, Einstellung ohne Auto-Increment-Spalte
            'INSERT INTO `pdl3_settingsgroup` (`sgroup_id`, `name`) VALUES (?, ?) | 10 | Neue Gruppe',
            'INSERT INTO `pdl3_settings` (`variablenname`, `name`, `bez`, `wert`, `sgroup_id`) VALUES (?, ?, ?, ?, ?) | site_url | Adresse der Download-Seite | Mit https:// |  | 4',
            // e) Beschriftung „name“ unverändert seit 3.5.0, „bez“ vom Betreiber geändert
            'UPDATE `pdl3_settings` SET `name` = ? WHERE `variablenname` = ? | Adresse des Skripts | script_file',
            // e) Vorlage unverändert seit 3.5.0 (nur CRLF)
            "UPDATE `pdl3_template` SET `wert` = ? WHERE `variablenname` = ? | <div class=\"neu\">\n{rows}\n</div> | top_box",
            // f) Installationszeitpunkt
            "UPDATE `pdl3_settings` SET `wert` = ? WHERE `variablenname` = 'installed' | 1790000000",
        ], self::sqlOf($plan));

        self::assertSame([
            ['table' => 'pdl3_settings', 'key' => 'script_file', 'column' => 'bez'],
            ['table' => 'pdl3_template', 'key' => 'flop_box', 'column' => 'wert'],
        ], $plan['kept']);
    }

    #[Test]
    public function settingValuesOfTheOperatorAreNeverTouched(): void
    {
        foreach (self::updater()->plan(self::snapshot350(), 1)['actions'] as $action) {
            foreach ($action['queries'] as $query) {
                if (str_starts_with($query['sql'], 'UPDATE `pdl3_settings` SET `wert`')) {
                    self::assertStringContainsString("'installed'", $query['sql']);
                }
            }
        }

        self::assertTrue(true);
    }

    #[Test]
    public function missingTablesAreCreatedWithTheirData(): void
    {
        $snapshot = self::snapshot350();
        $snapshot['tables'] = array_values(array_diff($snapshot['tables'], ['pdl3_comments', 'pdl3_settingsgroup']));
        unset($snapshot['columns']['pdl3_comments'], $snapshot['columns']['pdl3_settingsgroup'], $snapshot['rows']['pdl3_settingsgroup']);

        $plan = self::updater()->plan($snapshot, 1);
        $creates = array_values(array_filter($plan['actions'], static fn (array $a): bool => $a['kind'] === Updater::KIND_CREATE_TABLE));

        self::assertSame(['pdl3_settingsgroup', 'pdl3_comments'], array_column($creates, 'table'));
        self::assertSame('Tabelle pdl3_settingsgroup anlegen (mit 2 Datensätzen)', $creates[0]['label']);
        self::assertCount(3, $creates[0]['queries']);
        self::assertStringStartsWith('CREATE TABLE `pdl3_settingsgroup`', $creates[0]['queries'][0]['sql']);
        self::assertSame('Tabelle pdl3_comments anlegen', $creates[1]['label']);
        self::assertSame(Updater::KIND_CREATE_TABLE, $plan['actions'][0]['kind'], 'Tabellen kommen zuerst.');

        foreach ($plan['actions'] as $action) {
            if ($action['kind'] !== Updater::KIND_CREATE_TABLE) {
                self::assertNotSame('pdl3_settingsgroup', $action['table'], 'Neue Tabellen bekommen keine zusätzlichen INSERTs.');
            }
        }
    }

    #[Test]
    public function upToDateDatabaseHasNothingToDo(): void
    {
        $statements = Schema::split(self::SCHEMA);
        $snapshot = ['tables' => [], 'columns' => [], 'rows' => []];

        foreach ($statements as $statement) {
            $table = Schema::createdTable($statement);

            if ($table !== null) {
                $snapshot['tables'][] = $table;

                foreach (Schema::columns($statement) as $column => $definition) {
                    $snapshot['columns'][$table][$column] = (string) preg_replace('/\s+(NOT NULL|NULL|DEFAULT|AUTO_INCREMENT).*$/i', '', $definition);
                }
            }
        }

        foreach (Schema::rows($statements) as $table => $rows) {
            $snapshot['rows'][$table] = $rows;
        }

        $plan = self::updater()->plan($snapshot, 1);
        self::assertSame(["UPDATE `pdl3_settings` SET `wert` = ? WHERE `variablenname` = 'installed' | 1"], self::sqlOf($plan));

        foreach ($snapshot['rows']['pdl3_settings'] as $index => $row) {
            if ($row['variablenname'] === 'installed') {
                $snapshot['rows']['pdl3_settings'][$index]['wert'] = '1790000000';
            }
        }

        self::assertSame(['actions' => [], 'kept' => [], 'notes' => []], self::updater()->plan($snapshot, 1), 'Zweiter Lauf: nichts zu tun.');
    }

    #[Test]
    public function realSchemaAgainstAn350DatabaseOnlyAddsAndLifts(): void
    {
        $root = self::rootDir();
        $updater = Updater::fromFiles($root, \PowerDownload\Installer\Setup::configuredTables($root));
        $statements = Schema::fromFile($root . '/' . Schema::FILENAME);
        $defaults = Schema::rows(Schema::fromFile($root . '/' . Updater::DEFAULTS_FILE));

        // 3.5.0-Datenbank: alle Tabellen und Spalten des Schemas, Grunddaten aus 3.5.0
        $snapshot = ['tables' => [], 'columns' => [], 'rows' => []];

        foreach ($statements as $statement) {
            $table = Schema::createdTable($statement);

            if ($table !== null) {
                $snapshot['tables'][] = $table;

                foreach (Schema::columns($statement) as $column => $definition) {
                    $snapshot['columns'][$table][$column] = (string) preg_replace('/\s+(NOT NULL|NULL|DEFAULT|AUTO_INCREMENT).*$/i', '', $definition);
                }
            }
        }

        $snapshot['rows'] = $defaults + ['pdl3_usergroup' => Schema::rows($statements)['pdl3_usergroup'] ?? []];
        $plan = $updater->plan($snapshot, 1_790_000_000, ['site_url' => 'https://www.example.org/downloads']);
        $labels = array_column($plan['actions'], 'label');

        self::assertContains('Einstellung „site_url“ (Adresse der Download-Seite) mit dem Wert „https://www.example.org/downloads“ ergänzen', $labels);
        self::assertContains('Einstellung „sitename“ (Name der Download-Seite) mit dem Wert „PowerDownload“ ergänzen', $labels);
        self::assertContains('Installationsdatum auf heute setzen (bisher 0)', $labels);
        self::assertSame([], $plan['kept'], 'Ein unveränderter 3.5.0-Stand hat keine eigenen Anpassungen.');

        foreach ($plan['actions'] as $action) {
            self::assertContains($action['kind'], [Updater::KIND_INSERT_ROW, Updater::KIND_LIFT, Updater::KIND_INSTALLED], $action['label']);
        }
    }

    /**
     * Schema mit den Benutzergruppen von 3.6.0, der Einstellung guest_group_id
     * und der neuen Vorlagengruppe 9 „E-Mails“.
     */
    private const string SCHEMA_GROUPS = <<<'SQL'
        CREATE TABLE `pdl3_settings` (`setting_id` int unsigned NOT NULL AUTO_INCREMENT, `variablenname` varchar(64) NOT NULL DEFAULT '', `name` varchar(128) NOT NULL DEFAULT '', `wert` text NOT NULL, `sgroup_id` int NOT NULL DEFAULT '0', PRIMARY KEY (`setting_id`));
        INSERT INTO `pdl3_settings` (`setting_id`, `variablenname`, `name`, `wert`, `sgroup_id`) VALUES (50,'guest_group_id','Gruppe für Gäste','3',2);
        CREATE TABLE `pdl3_usergroup` (`ugroup_id` int unsigned NOT NULL AUTO_INCREMENT, `name` varchar(128) NOT NULL DEFAULT '', `addcomments` enum('Y','N') NOT NULL DEFAULT 'Y', `download` enum('Y','N') NOT NULL DEFAULT 'Y', `vote` enum('Y','N') NOT NULL DEFAULT 'Y', `adminaccess` enum('Y','N') NOT NULL DEFAULT 'N', PRIMARY KEY (`ugroup_id`));
        INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `addcomments`, `download`, `vote`, `adminaccess`) VALUES (1,'Mitglied','Y','Y','Y','N');
        INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `addcomments`, `download`, `vote`, `adminaccess`) VALUES (2,'Administrator','Y','Y','Y','Y');
        INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `addcomments`, `download`, `vote`, `adminaccess`) VALUES (3,'Gast','N','Y','N','N');
        CREATE TABLE `pdl3_templategroup` (`tgroup_id` int unsigned NOT NULL AUTO_INCREMENT, `name` varchar(128) NOT NULL DEFAULT '', PRIMARY KEY (`tgroup_id`));
        INSERT INTO `pdl3_templategroup` (`tgroup_id`, `name`) VALUES (3,'Farben');
        INSERT INTO `pdl3_templategroup` (`tgroup_id`, `name`) VALUES (9,'E-Mails');
        CREATE TABLE `pdl3_template` (`template_id` int unsigned NOT NULL AUTO_INCREMENT, `variablenname` varchar(64) NOT NULL DEFAULT '', `name` varchar(128) NOT NULL DEFAULT '', `wert` text NOT NULL, `tgroup_id` int NOT NULL DEFAULT '0', PRIMARY KEY (`template_id`));
        INSERT INTO `pdl3_template` (`template_id`, `variablenname`, `name`, `wert`, `tgroup_id`) VALUES (50,'mail_register','E-Mail: Registrierung','Hallo {nick}',9);
        CREATE TABLE `pdl3_user` (`user_id` int unsigned NOT NULL AUTO_INCREMENT, `get_letter` enum('Y','N') NOT NULL DEFAULT 'N', `ugroup_id` int NOT NULL DEFAULT '1', PRIMARY KEY (`user_id`));
        SQL;

    private const string DEFAULTS_GROUPS = <<<'SQL'
        INSERT INTO `pdl3_templategroup` (`tgroup_id`, `name`) VALUES (3,'Farben');
        SQL;

    /**
     * 3.5.0-Stand zum Schema SCHEMA_GROUPS.
     *
     * @param list<array<string, string|null>> $groups
     * @param list<array<string, string|null>> $templateGroups
     *
     * @return Snapshot
     */
    private static function groupSnapshot(array $groups, array $templateGroups = [['tgroup_id' => '3', 'name' => 'Farben']], array $settings = []): array
    {
        return [
            'tables' => ['pdl3_settings', 'pdl3_usergroup', 'pdl3_templategroup', 'pdl3_template', 'pdl3_user'],
            'columns' => [
                'pdl3_settings' => ['setting_id' => 'int unsigned', 'variablenname' => 'varchar(64)', 'name' => 'varchar(128)', 'wert' => 'text', 'sgroup_id' => 'int'],
                'pdl3_usergroup' => ['ugroup_id' => 'int unsigned', 'name' => 'varchar(128)', 'addcomments' => "enum('Y','N')", 'download' => "enum('Y','N')", 'vote' => "enum('Y','N')", 'adminaccess' => "enum('Y','N')"],
                'pdl3_templategroup' => ['tgroup_id' => 'int unsigned', 'name' => 'varchar(128)'],
                'pdl3_template' => ['template_id' => 'int unsigned', 'variablenname' => 'varchar(64)', 'name' => 'varchar(128)', 'wert' => 'text', 'tgroup_id' => 'int'],
                'pdl3_user' => ['user_id' => 'int unsigned', 'get_letter' => "enum('Y','N')", 'ugroup_id' => 'int'],
            ],
            'defaults' => [
                'pdl3_user' => ['user_id' => null, 'get_letter' => 'Y', 'ugroup_id' => '1'],
                'pdl3_usergroup' => ['ugroup_id' => null, 'name' => '', 'addcomments' => "'Y'", 'download' => "'Y'", 'vote' => 'Y', 'adminaccess' => 'N'],
            ],
            'rows' => [
                'pdl3_settings' => $settings,
                'pdl3_usergroup' => $groups,
                'pdl3_templategroup' => $templateGroups,
                'pdl3_template' => [],
            ],
        ];
    }

    private const array GROUP_1_350 = ['ugroup_id' => '1', 'name' => 'Gast', 'addcomments' => 'Y', 'download' => 'Y', 'vote' => 'N', 'adminaccess' => 'N'];

    private const array GROUP_2 = ['ugroup_id' => '2', 'name' => 'Administrator', 'addcomments' => 'Y', 'download' => 'Y', 'vote' => 'Y', 'adminaccess' => 'Y'];

    #[Test]
    public function guestGroupIsCreatedAndGroupOneBecomesMember(): void
    {
        $plan = self::updater(self::SCHEMA_GROUPS, self::DEFAULTS_GROUPS)->plan(self::groupSnapshot([self::GROUP_1_350, self::GROUP_2]), 1);

        self::assertSame([
            "ALTER TABLE `pdl3_user` ALTER COLUMN `get_letter` SET DEFAULT 'N'",
            'INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `addcomments`, `download`, `vote`, `adminaccess`) VALUES (?, ?, ?, ?, ?, ?) | 3 | Gast | N | Y | N | N',
            'UPDATE `pdl3_usergroup` SET `name` = ?, `vote` = ? WHERE `ugroup_id` = 1 AND `name` = ? AND `vote` = ? AND `addcomments` = ? AND `download` = ? AND `adminaccess` = ? | Mitglied | Y | Gast | N | Y | Y | N',
            'INSERT INTO `pdl3_templategroup` (`tgroup_id`, `name`) VALUES (?, ?) | 9 | E-Mails',
            'INSERT INTO `pdl3_settings` (`variablenname`, `name`, `wert`, `sgroup_id`) VALUES (?, ?, ?, ?) | guest_group_id | Gruppe für Gäste | 3 | 2',
            'INSERT INTO `pdl3_template` (`variablenname`, `name`, `wert`, `tgroup_id`) VALUES (?, ?, ?, ?) | mail_register | E-Mail: Registrierung | Hallo {nick} | 9',
        ], self::sqlOf($plan));
        self::assertSame([], $plan['notes']);
    }

    #[Test]
    public function occupiedIdsLeadToTheNextFreeIdAndAreRemapped(): void
    {
        $plan = self::updater(self::SCHEMA_GROUPS, self::DEFAULTS_GROUPS)->plan(self::groupSnapshot(
            [self::GROUP_1_350, self::GROUP_2, ['ugroup_id' => '3', 'name' => 'Redaktion', 'addcomments' => 'Y', 'download' => 'Y', 'vote' => 'Y', 'adminaccess' => 'Y']],
            [['tgroup_id' => '3', 'name' => 'Farben'], ['tgroup_id' => '9', 'name' => 'Eigene Gruppe']],
        ), 1);
        $sql = self::sqlOf($plan);

        self::assertContains('INSERT INTO `pdl3_usergroup` (`ugroup_id`, `name`, `addcomments`, `download`, `vote`, `adminaccess`) VALUES (?, ?, ?, ?, ?, ?) | 4 | Gast | N | Y | N | N', $sql);
        self::assertContains('INSERT INTO `pdl3_settings` (`variablenname`, `name`, `wert`, `sgroup_id`) VALUES (?, ?, ?, ?) | guest_group_id | Gruppe für Gäste | 4 | 2', $sql);
        self::assertContains('INSERT INTO `pdl3_templategroup` (`tgroup_id`, `name`) VALUES (?, ?) | 10 | E-Mails', $sql);
        self::assertContains('INSERT INTO `pdl3_template` (`variablenname`, `name`, `wert`, `tgroup_id`) VALUES (?, ?, ?, ?) | mail_register | E-Mail: Registrierung | Hallo {nick} | 10', $sql);

        // Zweiter Lauf: Gruppen und Vorlage sind da, nichts wird doppelt angelegt.
        $after = self::groupSnapshot(
            [self::GROUP_1_350, self::GROUP_2, ['ugroup_id' => '3', 'name' => 'Redaktion'], ['ugroup_id' => '4', 'name' => 'Gast']],
            [['tgroup_id' => '3', 'name' => 'Farben'], ['tgroup_id' => '9', 'name' => 'Eigene Gruppe'], ['tgroup_id' => '10', 'name' => 'E-Mails']],
            [['variablenname' => 'guest_group_id', 'name' => 'Gruppe für Gäste', 'wert' => '4', 'sgroup_id' => '2']],
        );
        $after['rows']['pdl3_template'] = [['template_id' => '7', 'variablenname' => 'mail_register', 'name' => 'E-Mail: Registrierung', 'wert' => 'Hallo {nick}', 'tgroup_id' => '10']];
        $after['defaults']['pdl3_user']['get_letter'] = "'N'";

        self::assertSame(['actions' => [], 'kept' => [], 'notes' => []], self::updater(self::SCHEMA_GROUPS, self::DEFAULTS_GROUPS)->plan($after, 1));
    }

    #[Test]
    public function existingGuestGroupIsTakenOverAndCustomizedGroupOneOnlyGetsANote(): void
    {
        $customized = ['vote' => 'Y'] + self::GROUP_1_350;
        $plan = self::updater(self::SCHEMA_GROUPS, self::DEFAULTS_GROUPS)->plan(self::groupSnapshot(
            [$customized, self::GROUP_2, ['ugroup_id' => '3', 'name' => 'Gast', 'addcomments' => 'N', 'download' => 'Y', 'vote' => 'N', 'adminaccess' => 'N']],
        ), 1);

        foreach ($plan['actions'] as $action) {
            self::assertNotSame(Updater::KIND_USERGROUP, $action['kind'], $action['label']);
        }

        self::assertContains('INSERT INTO `pdl3_settings` (`variablenname`, `name`, `wert`, `sgroup_id`) VALUES (?, ?, ?, ?) | guest_group_id | Gruppe für Gäste | 3 | 2', self::sqlOf($plan));
        self::assertCount(1, $plan['notes']);
        self::assertStringContainsString('Benutzergruppe 1 („Gast“)', $plan['notes'][0]);
    }

    #[Test]
    public function columnDefaultsFromMysqlAndMariaDb(): void
    {
        self::assertSame(['found' => true, 'value' => 'N', 'sql' => "'N'"], Updater::schemaDefault("enum('Y','N') NOT NULL DEFAULT 'N'"));
        self::assertSame(['found' => true, 'value' => '0', 'sql' => "'0'"], Updater::schemaDefault("int NOT NULL DEFAULT '0'"));
        self::assertSame(['found' => true, 'value' => '5', 'sql' => '5'], Updater::schemaDefault('int NOT NULL DEFAULT 5'));
        self::assertFalse(Updater::schemaDefault('text NOT NULL')['found']);
        self::assertSame('N', Updater::dbDefault("'N'"));
        self::assertSame('N', Updater::dbDefault('N'));
        self::assertSame('', Updater::dbDefault("''"));
        self::assertNull(Updater::dbDefault('NULL'));
        self::assertNull(Updater::dbDefault(null));
    }

    #[Test]
    public function widenedDefinitions(): void
    {
        self::assertNull(Updater::widenedDefinition("varchar(128) NOT NULL DEFAULT ''", 'varchar(255)'), 'Nie verkleinern.');
        self::assertNull(Updater::widenedDefinition("int NOT NULL DEFAULT '0'", 'int(11)'));
        self::assertNull(Updater::widenedDefinition('int unsigned NOT NULL AUTO_INCREMENT', 'int(10) unsigned'));
        self::assertNull(Updater::widenedDefinition('text NOT NULL', 'mediumtext'));
        self::assertNull(Updater::widenedDefinition("enum('Y','N') NOT NULL DEFAULT 'Y'", "enum('Y','N','X')"));
        self::assertNull(Updater::widenedDefinition("enum('Y','N') NOT NULL", 'varchar(1)'), 'Andere Typfamilien bleiben unangetastet.');
        self::assertSame('text NOT NULL', Updater::widenedDefinition('text NOT NULL', 'varchar(255)'));
        self::assertSame('mediumtext NOT NULL', Updater::widenedDefinition('mediumtext NOT NULL', 'text'));
        self::assertSame("smallint NOT NULL DEFAULT '0'", Updater::widenedDefinition("smallint NOT NULL DEFAULT '0'", 'tinyint(4)'));
        self::assertSame(
            "enum('a','it''s') NOT NULL DEFAULT 'a'",
            Updater::widenedDefinition("enum('a','it''s') NOT NULL DEFAULT 'a'", "enum('a')"),
        );
        self::assertSame(['a', "it's", 'b,c'], Updater::enumValues("'a','it''s','b,c'"));
    }

    #[Test]
    public function sameTextIgnoresOnlyLineEndings(): void
    {
        self::assertTrue(Updater::sameText("a\r\nb", "a\nb"));
        self::assertTrue(Updater::sameText("a\rb", "a\nb"));
        self::assertFalse(Updater::sameText('a b', 'a  b'));
        self::assertFalse(Updater::sameText(null, ''));
        self::assertTrue(Updater::sameText(null, null));
    }
}
