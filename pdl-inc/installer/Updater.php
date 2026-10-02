<?php

/**
 * PowerDownload - Datenbank-Update bestehender Installationen (update.php)
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

/**
 * Gleicht eine bestehende Datenbank mit pdl-inc/pdl3_schema.sql ab.
 *
 * Es gibt keine gespeicherte Versionsnummer, die falsch sein könnte:
 * Maßgeblich ist der tatsächliche Zustand der Datenbank. Jeder Lauf ist
 * beliebig oft wiederholbar und ergänzt nur, was fehlt:
 *
 *  a) fehlende Tabellen (samt ihren Grunddaten),
 *  b) fehlende Spalten (ALTER TABLE … ADD COLUMN, Definition aus dem Schema);
 *     vorhandene Einträge erhalten für neue Spalten den Wert aus dem Schema,
 *  c) zu kleine Spaltentypen (neue ENUM-Werte, längere VARCHAR, größere
 *     Ganzzahl- und Texttypen), nie eine Verkleinerung, sowie geänderte
 *     Vorgabewerte (ALTER … SET DEFAULT, vorhandene Werte bleiben),
 *  d) fehlende Datensätze in pdl3_settings, pdl3_settingsgroup,
 *     pdl3_template, pdl3_templategroup und pdl3_rights, eingefügt nach
 *     Schlüssel (variablenname) statt nach fester ID; ist die ID einer
 *     neuen Gruppe belegt, bekommt sie eine freie ID,
 *  e) Vorlagen und Beschriftungen, die noch exakt dem Auslieferungsstand
 *     3.5.0 entsprechen, auf den neuen Stand. Vom Betreiber geänderte
 *     Einträge bleiben unverändert und werden gemeldet,
 *  f) die Benutzergruppen von 3.6.0: Gruppe „Gast“ für nicht angemeldete
 *     Besucher (guest_group_id), Gruppe 1 wird „Mitglied“, aber nur, wenn
 *     sie noch exakt dem Stand 3.5.0 entspricht,
 *  g) den Installationszeitpunkt, falls er noch 0 ist.
 *
 * Gelöscht oder verkleinert wird nichts. plan() ist eine reine Funktion auf
 * Arrays und ohne Datenbank testbar; snapshot() liest die Datenbank,
 * apply() führt einen Plan aus.
 *
 * @phpstan-type Row array<string, string|null>
 * @phpstan-type Snapshot array{tables: list<string>, columns: array<string, array<string, string>>, rows: array<string, list<Row>>, defaults?: array<string, array<string, string|null>>}
 * @phpstan-type Query array{sql: string, params: list<string|null>}
 * @phpstan-type Action array{kind: string, table: string, label: string, queries: list<Query>}
 * @phpstan-type Kept array{table: string, key: string, column: string}
 * @phpstan-type Plan array{actions: list<Action>, kept: list<Kept>, notes: list<string>}
 * @phpstan-type LogEntry array{kind: string, label: string, ok: bool, message: string}
 * @phpstan-type TableConfig array{key: string, auto: string, lift: list<string>, noun: string, refs: array<string, string>}
 */
final class Updater
{
    /**
     * Grunddaten von PowerDownload 3.5.0 (nur Vergleichsdaten, nie einspielen).
     */
    public const string DEFAULTS_FILE = 'pdl-inc/installer/defaults-3.5.0.sql';

    public const string KIND_CREATE_TABLE = 'create_table';

    public const string KIND_ADD_COLUMN = 'add_column';

    public const string KIND_MODIFY_COLUMN = 'modify_column';

    public const string KIND_COLUMN_DEFAULT = 'column_default';

    public const string KIND_FILL_COLUMN = 'fill_column';

    public const string KIND_USERGROUP = 'usergroup';

    public const string KIND_INSERT_ROW = 'insert_row';

    public const string KIND_LIFT = 'lift';

    public const string KIND_INSTALLED = 'installed';

    /**
     * Überschriften der Arten in der Vorschau, in Ausführungsreihenfolge.
     */
    public const array KIND_LABELS = [
        self::KIND_CREATE_TABLE => 'Tabellen anlegen',
        self::KIND_ADD_COLUMN => 'Spalten ergänzen',
        self::KIND_MODIFY_COLUMN => 'Spaltentypen erweitern',
        self::KIND_COLUMN_DEFAULT => 'Vorgabewerte von Spalten anpassen (vorhandene Einträge bleiben unverändert)',
        self::KIND_FILL_COLUMN => 'Werte für neue Spalten übernehmen',
        self::KIND_USERGROUP => 'Benutzergruppen anpassen',
        self::KIND_INSERT_ROW => 'Datensätze ergänzen',
        self::KIND_LIFT => 'Vorlagen und Beschriftungen aktualisieren (unverändert seit 3.5.0)',
        self::KIND_INSTALLED => 'Installationsdatum setzen',
    ];

    /**
     * Tabellen mit Grunddaten: Schlüsselspalte, Auto-Increment-Spalte (wird
     * beim Ergänzen weggelassen, die Datenbank vergibt die Nummer), Spalten,
     * die gehoben werden, solange sie dem Stand 3.5.0 entsprechen, und
     * Verweise anderer Tabellen auf die ID (für Gruppen mit belegter ID).
     * Einstellungswerte (pdl3_settings.wert) gehören dem Betreiber und
     * werden nie gehoben. Gruppen stehen vor den Tabellen, die auf sie verweisen.
     */
    public const array DATA_TABLES = [
        'pdl3_settingsgroup' => ['key' => 'sgroup_id', 'auto' => '', 'lift' => ['name'], 'noun' => 'Einstellungsgruppe', 'refs' => ['pdl3_settings' => 'sgroup_id']],
        'pdl3_templategroup' => ['key' => 'tgroup_id', 'auto' => '', 'lift' => ['name'], 'noun' => 'Vorlagengruppe', 'refs' => ['pdl3_template' => 'tgroup_id']],
        'pdl3_rights' => ['key' => 'variablenname', 'auto' => 'right_id', 'lift' => ['name', 'bez'], 'noun' => 'Recht', 'refs' => []],
        'pdl3_settings' => ['key' => 'variablenname', 'auto' => 'setting_id', 'lift' => ['name', 'bez'], 'noun' => 'Einstellung', 'refs' => []],
        'pdl3_template' => ['key' => 'variablenname', 'auto' => 'template_id', 'lift' => ['name', 'bez', 'wert'], 'noun' => 'Vorlage', 'refs' => []],
    ];

    /**
     * Weitere Tabellen, deren vorhandene Einträge bei einer neuen Spalte den
     * Wert aus dem Schema erhalten (z. B. ein neues Recht für die Gruppe
     * „Administrator“). Neue Einträge legt der allgemeine Abgleich hier
     * nicht an; die Gruppe „Gast“ ergänzt der Abschnitt f).
     */
    public const array FILL_ONLY_TABLES = ['pdl3_usergroup' => 'ugroup_id'];

    /**
     * Gruppe 1 in 3.5.0: hieß „Gast“, war aber die Gruppe neuer Mitglieder.
     * Nur in genau diesem Zustand wird sie zu „Mitglied“ (mit Bewerten).
     */
    public const array MEMBER_GROUP_350 = ['name' => 'Gast', 'vote' => 'N', 'addcomments' => 'Y', 'download' => 'Y', 'adminaccess' => 'N'];

    private const int MEMBER_GROUP = 1;

    /**
     * Einstellungen, deren Wert die Vorschau beim Ergänzen nennt.
     */
    private const array SHOWN_VALUES = ['site_url', 'sitename', 'guest_group_id'];

    /**
     * Spaltennamen in Meldungen.
     */
    private const array COLUMN_LABELS = ['name' => 'Bezeichnung', 'bez' => 'Beschreibung', 'wert' => 'Inhalt'];

    private const array INT_RANKS = ['tinyint' => 1, 'smallint' => 2, 'mediumint' => 3, 'int' => 4, 'integer' => 4, 'bigint' => 5];

    private const array TEXT_RANKS = ['tinytext' => 1, 'text' => 2, 'mediumtext' => 3, 'longtext' => 4];

    /**
     * @param list<string> $statements Anweisungen aus pdl3_schema.sql
     * @param array<string, array<array-key, Row>> $defaults Grunddaten 3.5.0 je Tabelle und Schlüssel
     */
    public function __construct(
        private readonly array $statements,
        private readonly array $defaults,
    ) {
    }

    /**
     * Liest Schema und Vergleichsdaten aus dem PowerDownload-Verzeichnis.
     *
     * @param list<string> $requiredTables Tabellen aus $sql_table
     *
     * @throws \RuntimeException wenn eine Datei fehlt
     * @throws \UnexpectedValueException wenn das Schema unerwartete Anweisungen enthält
     */
    public static function fromFiles(string $rootDir, array $requiredTables): self
    {
        $statements = Schema::fromFile($rootDir . '/' . Schema::FILENAME);
        Schema::assertInstallable($statements, $requiredTables);
        $defaults = self::keyRows(Schema::rows(Schema::fromFile($rootDir . '/' . self::DEFAULTS_FILE)));

        return new self($statements, $defaults);
    }

    /**
     * Ordnet Datensätze je Tabelle nach ihrem Schlüssel (nur Tabellen mit
     * bekanntem Schlüssel).
     *
     * @param array<string, list<Row>> $rows
     *
     * @return array<string, array<array-key, Row>>
     */
    public static function keyRows(array $rows): array
    {
        $keyed = [];

        foreach ($rows as $table => $tableRows) {
            $key = self::keyColumn($table);

            if ($key === null) {
                continue;
            }

            foreach ($tableRows as $row) {
                $value = $row[$key] ?? null;

                if ($value !== null) {
                    $keyed[$table][$value] = $row;
                }
            }
        }

        return $keyed;
    }

    /**
     * Liest Tabellen, Spaltentypen, Vorgabewerte und die Grunddaten-Tabellen
     * der Datenbank.
     *
     * @return Snapshot
     */
    public static function snapshot(\mysqli $mysqli): array
    {
        $tables = DatabaseSetup::existingTables($mysqli);
        $columns = [];
        $defaults = [];
        $result = $mysqli->query(
            'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT FROM information_schema.COLUMNS'
            . " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'pdl3\\_%' ORDER BY TABLE_NAME, ORDINAL_POSITION",
        );

        while ($result instanceof \mysqli_result && is_array($row = $result->fetch_row())) {
            $columns[(string) $row[0]][(string) $row[1]] = (string) $row[2];
            $defaults[(string) $row[0]][(string) $row[1]] = $row[3] === null ? null : (string) $row[3];
        }

        $rows = [];

        foreach (array_merge(array_keys(self::DATA_TABLES), array_keys(self::FILL_ONLY_TABLES)) as $table) {
            if (!in_array($table, $tables, true)) {
                continue;
            }

            $rows[$table] = [];
            $data = $mysqli->query('SELECT * FROM `' . $table . '`');

            while ($data instanceof \mysqli_result && is_array($row = $data->fetch_assoc())) {
                $clean = [];

                foreach ($row as $column => $value) {
                    $clean[$column] = $value === null ? null : (string) $value;
                }

                $rows[$table][] = $clean;
            }
        }

        return ['tables' => $tables, 'columns' => $columns, 'rows' => $rows, 'defaults' => $defaults];
    }

    /**
     * Berechnet, was zu tun ist. Ändert nichts.
     *
     * @param Snapshot $db
     * @param array<string, string> $newSettings Werte für Einstellungen, die ergänzt werden
     *                                          (variablenname => wert), z. B. site_url aus der Anfrage
     *
     * @return Plan
     */
    public function plan(array $db, int $now, array $newSettings = []): array
    {
        $creates = [];
        $inserts = [];

        foreach ($this->statements as $statement) {
            $created = Schema::createdTable($statement);

            if ($created !== null) {
                $creates[$created] = $statement;
                continue;
            }

            $inserted = Schema::insertedTable($statement);

            if ($inserted !== null) {
                $inserts[$inserted][] = $statement;
            }
        }

        $schemaRows = self::keyRows(Schema::rows($this->statements));

        $actions = self::tableActions($db, $creates, $inserts);
        [$columnActions, $newColumns] = self::columnActions($db, $creates);
        $actions = array_merge($actions, $columnActions, self::fillActions($db, $newColumns, $schemaRows));

        [$groupActions, $notes, $overrides] = self::userGroupActions($db, $schemaRows);
        foreach ($newSettings as $name => $value) {
            $overrides['pdl3_settings'][$name] = ['wert' => $value] + ($overrides['pdl3_settings'][$name] ?? []);
        }

        [$dataActions, $kept] = $this->dataActions($db, $schemaRows, $overrides, $now);
        $actions = array_merge($actions, $groupActions, $dataActions);

        // g) Installationszeitpunkt
        $installed = self::keyedDbRows($db, 'pdl3_settings', 'variablenname')['installed'] ?? null;

        if ($installed !== null && in_array(trim((string) ($installed['wert'] ?? '')), ['', '0'], true)) {
            $actions[] = self::action(
                self::KIND_INSTALLED,
                'pdl3_settings',
                'Installationsdatum auf heute setzen (bisher 0)',
                [['sql' => "UPDATE `pdl3_settings` SET `wert` = ? WHERE `variablenname` = 'installed'", 'params' => [(string) $now]]],
            );
        }

        return ['actions' => self::sortActions($actions), 'kept' => $kept, 'notes' => $notes];
    }

    /**
     * Führt einen Plan aus. Ein Fehler beendet nur die betroffene Aktion;
     * die übrigen laufen weiter. Ein erneuter Lauf erledigt den Rest.
     *
     * @param Plan $plan
     *
     * @return list<LogEntry>
     */
    public static function apply(\mysqli $mysqli, array $plan): array
    {
        $log = [];

        foreach ($plan['actions'] as $action) {
            try {
                foreach ($action['queries'] as $query) {
                    self::execute($mysqli, $query);
                }
                $log[] = ['kind' => $action['kind'], 'label' => $action['label'], 'ok' => true, 'message' => 'erledigt'];
            } catch (\mysqli_sql_exception $e) {
                error_log('PowerDownload-Update: ' . $action['label'] . ' fehlgeschlagen (Fehlercode ' . $e->getCode() . ').');
                $log[] = ['kind' => $action['kind'], 'label' => $action['label'], 'ok' => false, 'message' => DatabaseSetup::friendlyError($e->getCode())];
            }
        }

        return $log;
    }

    /**
     * Neue Definition einer vorhandenen Spalte, wenn der Typ in der
     * Datenbank zu klein ist, sonst null. Verkleinert wird nie; ENUM-Werte,
     * die nur die Datenbank kennt, bleiben erhalten.
     */
    public static function widenedDefinition(string $schemaDefinition, string $dbType): ?string
    {
        $schema = self::parseType($schemaDefinition);
        $current = self::parseType($dbType);

        if ($schema === null || $current === null) {
            return null;
        }

        $base = $schema['base'];
        $currentBase = $current['base'];

        if (($base === 'enum' || $base === 'set') && $base === $currentBase) {
            $schemaValues = self::enumValues($schema['args']);
            $currentValues = self::enumValues($current['args']);

            if (array_diff($schemaValues, $currentValues) === []) {
                return null;
            }

            $merged = array_merge($schemaValues, array_values(array_diff($currentValues, $schemaValues)));
            $type = $base . '(' . implode(',', array_map(self::quote(...), $merged)) . ')';

            return $type . substr($schemaDefinition, strlen($schema['raw']));
        }

        if (isset(self::INT_RANKS[$base], self::INT_RANKS[$currentBase])) {
            return self::INT_RANKS[$base] > self::INT_RANKS[$currentBase] ? $schemaDefinition : null;
        }

        $isChar = static fn (string $type): bool => $type === 'char' || $type === 'varchar';

        if ($isChar($base) && $isChar($currentBase)) {
            return (int) $schema['args'] > (int) $current['args'] ? $schemaDefinition : null;
        }

        if (isset(self::TEXT_RANKS[$base]) && ($isChar($currentBase) || (isset(self::TEXT_RANKS[$currentBase]) && self::TEXT_RANKS[$base] > self::TEXT_RANKS[$currentBase]))) {
            return $schemaDefinition;
        }

        return null;
    }

    /**
     * Vorgabewert aus einer Spaltendefinition: ['found' => true, 'value' => …]
     * bei DEFAULT 'text', DEFAULT 123 oder DEFAULT NULL, sonst found = false.
     *
     * @return array{found: bool, value: string|null, sql: string}
     */
    public static function schemaDefault(string $definition): array
    {
        if (preg_match("/\\bDEFAULT\\s+('(?:[^'\\\\]|\\\\.|'')*'|-?\\d+(?:\\.\\d+)?|NULL)/i", $definition, $match) !== 1) {
            return ['found' => false, 'value' => null, 'sql' => ''];
        }

        $literal = $match[1];

        if (strtoupper($literal) === 'NULL') {
            return ['found' => true, 'value' => null, 'sql' => 'NULL'];
        }

        if ($literal[0] === "'") {
            return ['found' => true, 'value' => Schema::unescape(substr($literal, 1, -1), "'"), 'sql' => $literal];
        }

        return ['found' => true, 'value' => $literal, 'sql' => $literal];
    }

    /**
     * Vorgabewert wie ihn information_schema.COLUMNS.COLUMN_DEFAULT liefert,
     * vereinheitlicht: MySQL meldet „N“, MariaDB „'N'“ bzw. „NULL“.
     */
    public static function dbDefault(?string $raw): ?string
    {
        if ($raw === null || strtoupper($raw) === 'NULL') {
            return null;
        }

        if (strlen($raw) >= 2 && $raw[0] === "'" && $raw[strlen($raw) - 1] === "'") {
            return Schema::unescape(substr($raw, 1, -1), "'");
        }

        return $raw;
    }

    /**
     * Werte einer ENUM- oder SET-Liste, z. B. „'Y','N'“ → ['Y', 'N'].
     *
     * @return list<string>
     */
    public static function enumValues(string $list): array
    {
        $values = [];

        foreach (Schema::splitTopLevel($list, ',') as $item) {
            $item = trim($item);

            if (strlen($item) >= 2 && $item[0] === "'" && $item[strlen($item) - 1] === "'") {
                $values[] = Schema::unescape(substr($item, 1, -1), "'");
            }
        }

        return $values;
    }

    /**
     * Schlüsselspalte einer Tabelle mit Grunddaten oder null.
     */
    public static function keyColumn(string $table): ?string
    {
        return self::DATA_TABLES[$table]['key'] ?? self::FILL_ONLY_TABLES[$table] ?? null;
    }

    /**
     * Gleich bis auf Zeilenenden: Browser senden Textfelder mit CRLF. Eine
     * Vorlage, die im Adminbereich nur gespeichert, aber nicht geändert
     * wurde, gilt deshalb weiter als unverändert.
     */
    public static function sameText(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return self::unifyLineEndings($a) === self::unifyLineEndings($b);
    }

    /**
     * a) Fehlende Tabellen samt Grunddaten.
     *
     * @param Snapshot $db
     * @param array<string, string> $creates
     * @param array<string, list<string>> $inserts
     *
     * @return list<Action>
     */
    private static function tableActions(array $db, array $creates, array $inserts): array
    {
        $actions = [];

        foreach ($creates as $table => $create) {
            if (in_array($table, $db['tables'], true)) {
                continue;
            }

            $count = 0;

            foreach ($inserts[$table] ?? [] as $insert) {
                $count += count(Schema::parseInsert($insert)['rows']);
            }

            $actions[] = self::action(
                self::KIND_CREATE_TABLE,
                $table,
                'Tabelle ' . $table . ' anlegen' . ($count > 0 ? ' (mit ' . $count . ($count === 1 ? ' Datensatz)' : ' Datensätzen)') : ''),
                array_map(static fn (string $sql): array => ['sql' => $sql, 'params' => []], array_merge([$create], $inserts[$table] ?? [])),
            );
        }

        return $actions;
    }

    /**
     * b) und c) Spalten vorhandener Tabellen: ergänzen, erweitern,
     * Vorgabewert anpassen.
     *
     * @param Snapshot $db
     * @param array<string, string> $creates
     *
     * @return array{0: list<Action>, 1: array<string, list<string>>} Aktionen und neue Spalten je Tabelle
     */
    private static function columnActions(array $db, array $creates): array
    {
        $actions = [];
        $newColumns = [];

        foreach ($creates as $table => $create) {
            if (!in_array($table, $db['tables'], true)) {
                continue;
            }

            $existing = $db['columns'][$table] ?? [];
            $previous = null;

            foreach (Schema::columns($create) as $column => $definition) {
                if (!array_key_exists($column, $existing)) {
                    $actions[] = self::action(
                        self::KIND_ADD_COLUMN,
                        $table,
                        'Spalte ' . $table . '.' . $column . ' ergänzen',
                        [['sql' => 'ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition
                            . ($previous === null ? ' FIRST' : ' AFTER `' . $previous . '`'), 'params' => []]],
                    );
                    $newColumns[$table][] = $column;
                    $previous = $column;
                    continue;
                }

                $widened = self::widenedDefinition($definition, $existing[$column]);

                if ($widened !== null) {
                    $actions[] = self::action(
                        self::KIND_MODIFY_COLUMN,
                        $table,
                        'Spalte ' . $table . '.' . $column . ' erweitern (' . $existing[$column] . ' → ' . self::typeOf($widened) . ')',
                        [['sql' => 'ALTER TABLE `' . $table . '` MODIFY COLUMN `' . $column . '` ' . $widened, 'params' => []]],
                    );
                } elseif (isset($db['defaults'][$table]) && array_key_exists($column, $db['defaults'][$table])) {
                    $default = self::schemaDefault($definition);
                    $current = self::dbDefault($db['defaults'][$table][$column]);

                    if ($default['found'] && $default['value'] !== null && $default['value'] !== $current && stripos($definition, 'AUTO_INCREMENT') === false) {
                        $actions[] = self::action(
                            self::KIND_COLUMN_DEFAULT,
                            $table,
                            'Vorgabewert von ' . $table . '.' . $column . ' auf „' . $default['value'] . '“ setzen (bisher '
                                . ($current === null ? 'keiner' : '„' . $current . '“') . ')',
                            [['sql' => 'ALTER TABLE `' . $table . '` ALTER COLUMN `' . $column . '` SET DEFAULT ' . $default['sql'], 'params' => []]],
                        );
                    }
                }

                $previous = $column;
            }
        }

        return [$actions, $newColumns];
    }

    /**
     * b) Werte für neue Spalten in vorhandenen Einträgen.
     *
     * @param Snapshot $db
     * @param array<string, list<string>> $newColumns
     * @param array<string, array<array-key, Row>> $schemaRows
     *
     * @return list<Action>
     */
    private static function fillActions(array $db, array $newColumns, array $schemaRows): array
    {
        $actions = [];

        foreach ($newColumns as $table => $columns) {
            $key = self::keyColumn($table);

            if ($key === null) {
                continue;
            }

            $present = self::keyedDbRows($db, $table, $key);

            foreach ($columns as $column) {
                $queries = [];

                foreach ($schemaRows[$table] ?? [] as $keyValue => $row) {
                    if (isset($present[$keyValue]) && array_key_exists($column, $row) && $row[$column] !== null) {
                        $queries[] = [
                            'sql' => 'UPDATE `' . $table . '` SET `' . $column . '` = ? WHERE `' . $key . '` = ?',
                            'params' => [$row[$column], (string) $keyValue],
                        ];
                    }
                }

                if ($queries !== []) {
                    $actions[] = self::action(
                        self::KIND_FILL_COLUMN,
                        $table,
                        'Neue Spalte ' . $table . '.' . $column . ': Werte aus dem Schema für ' . self::entries(count($queries)) . ' übernehmen',
                        $queries,
                    );
                }
            }
        }

        return $actions;
    }

    /**
     * f) Benutzergruppen von 3.6.0. Läuft nur, solange die Einstellung
     * guest_group_id in der Datenbank fehlt (einmalige Umstellung).
     *
     * - Gruppe „Gast“ aus dem Schema: eine vorhandene Gruppe „Gast“ (nicht
     *   Gruppe 1, die in 3.5.0 so hieß) wird übernommen; sonst wird sie unter
     *   der Schema-ID angelegt oder, wenn diese belegt ist, unter der nächsten
     *   freien ID. guest_group_id zeigt auf diese Gruppe.
     * - Gruppe 1 wird nur dann zu „Mitglied“, wenn sie exakt dem Stand 3.5.0
     *   entspricht; sonst bleibt sie, und der Bericht weist darauf hin.
     *
     * @param Snapshot $db
     * @param array<string, array<array-key, Row>> $schemaRows
     *
     * @return array{0: list<Action>, 1: list<string>, 2: array<string, array<array-key, Row>>} Aktionen, Hinweise, Werte für ergänzte Datensätze
     */
    private static function userGroupActions(array $db, array $schemaRows): array
    {
        $guestSetting = $schemaRows['pdl3_settings']['guest_group_id'] ?? null;
        $settings = self::keyedDbRows($db, 'pdl3_settings', 'variablenname');

        if ($guestSetting === null || isset($settings['guest_group_id'])
            || !in_array('pdl3_usergroup', $db['tables'], true) || !in_array('pdl3_settings', $db['tables'], true)) {
            return [[], [], []];
        }

        $groups = self::keyedDbRows($db, 'pdl3_usergroup', 'ugroup_id');
        $schemaGroups = $schemaRows['pdl3_usergroup'] ?? [];
        $guestId = (string) ($guestSetting['wert'] ?? '');
        $guestRow = $schemaGroups[$guestId] ?? null;
        $actions = [];
        $notes = [];
        $overrides = [];

        if ($guestRow !== null) {
            $guestName = (string) ($guestRow['name'] ?? '');
            $existing = null;

            foreach ($groups as $id => $group) {
                $id = (string) $id;

                if ($id !== (string) self::MEMBER_GROUP && !(isset($schemaGroups[$id]) && $id !== $guestId) && ($group['name'] ?? null) === $guestName) {
                    $existing = $existing === null || (int) $id < (int) $existing ? $id : $existing;
                }
            }

            if ($existing === null) {
                $target = $guestId;

                if (isset($groups[$guestId])) {
                    $target = (string) (max(array_merge([0], array_map('intval', array_keys($groups)), array_map('intval', array_keys($schemaGroups)))) + 1);
                }

                $row = $guestRow;
                $row['ugroup_id'] = $target;
                $actions[] = self::action(
                    self::KIND_USERGROUP,
                    'pdl3_usergroup',
                    'Benutzergruppe „' . $guestName . '“ für nicht angemeldete Besucher anlegen (ID ' . $target . ($target !== $guestId ? ', weil ID ' . $guestId . ' belegt ist' : '') . ')',
                    [self::insertQuery('pdl3_usergroup', $row)],
                );
                $existing = $target;
            }

            $overrides['pdl3_settings']['guest_group_id'] = ['wert' => $existing];
        }

        $member = $groups[(string) self::MEMBER_GROUP] ?? null;
        $memberSchema = $schemaGroups[(string) self::MEMBER_GROUP] ?? null;

        if ($member !== null && $memberSchema !== null && ($member['name'] ?? null) !== ($memberSchema['name'] ?? null)) {
            $matches350 = true;

            foreach (self::MEMBER_GROUP_350 as $column => $value) {
                $matches350 = $matches350 && ($member[$column] ?? null) === $value;
            }

            if ($matches350) {
                $conditions = implode(' AND ', array_map(static fn (string $column): string => '`' . $column . '` = ?', array_keys(self::MEMBER_GROUP_350)));
                $actions[] = self::action(
                    self::KIND_USERGROUP,
                    'pdl3_usergroup',
                    'Benutzergruppe 1 „' . self::MEMBER_GROUP_350['name'] . '“ in „' . ($memberSchema['name'] ?? '') . '“ umbenennen und Bewerten erlauben (neue Mitglieder landen in dieser Gruppe)',
                    [[
                        'sql' => 'UPDATE `pdl3_usergroup` SET `name` = ?, `vote` = ? WHERE `ugroup_id` = ' . self::MEMBER_GROUP . ' AND ' . $conditions,
                        'params' => array_merge([$memberSchema['name'] ?? '', $memberSchema['vote'] ?? 'Y'], array_values(self::MEMBER_GROUP_350)),
                    ]],
                );
            } else {
                $notes[] = 'Die Benutzergruppe 1 („' . ($member['name'] ?? '') . '“) haben Sie angepasst; sie bleibt unverändert. '
                    . 'Neue Mitglieder landen weiterhin in dieser Gruppe. Bitte prüfen Sie im Adminbereich unter den Benutzergruppen, '
                    . 'welche Rechte neue Mitglieder haben sollen.';
            }
        }

        return [$actions, $notes, $overrides];
    }

    /**
     * d) fehlende Datensätze und e) unveränderte Vorlagen und Beschriftungen.
     *
     * @param Snapshot $db
     * @param array<string, array<array-key, Row>> $schemaRows
     * @param array<string, array<array-key, Row>> $overrides feste Werte für ergänzte Datensätze
     *
     * @return array{0: list<Action>, 1: list<Kept>}
     */
    private function dataActions(array $db, array $schemaRows, array $overrides, int $now): array
    {
        $actions = [];
        $kept = [];
        /** @var array<string, array<string, array<string, string>>> $remap Tabelle => Spalte => alte ID => neue ID */
        $remap = [];

        foreach (self::DATA_TABLES as $table => $config) {
            if (!in_array($table, $db['tables'], true)) {
                continue;
            }

            $present = self::keyedDbRows($db, $table, $config['key']);
            $usedIds = array_merge([0], array_map('intval', array_keys($present)), array_map('intval', array_keys($schemaRows[$table] ?? [])));

            foreach ($schemaRows[$table] ?? [] as $keyValue => $row) {
                $keyValue = (string) $keyValue;
                $row = array_replace($row, $overrides[$table][$keyValue] ?? []);

                foreach ($remap[$table] ?? [] as $column => $ids) {
                    if (isset($row[$column], $ids[$row[$column]])) {
                        $row[$column] = $ids[$row[$column]];
                    }
                }

                if (!isset($present[$keyValue])) {
                    $actions[] = $this->insertAction($table, $config, $keyValue, $row, $now);
                    continue;
                }

                // Neue Gruppe (nicht in 3.5.0), deren ID der Betreiber schon für etwas anderes nutzt
                if ($config['auto'] === '' && !isset($this->defaults[$table][$keyValue])
                    && !self::sameText($present[$keyValue]['name'] ?? null, $row['name'] ?? null)) {
                    $target = null;

                    foreach ($present as $id => $existing) {
                        if (self::sameText($existing['name'] ?? null, $row['name'] ?? null)) {
                            $target = (string) $id;
                            break;
                        }
                    }

                    if ($target === null) {
                        $target = (string) (max($usedIds) + 1);
                        $usedIds[] = (int) $target;
                        $moved = $row;
                        $moved[$config['key']] = $target;
                        $actions[] = $this->insertAction($table, $config, $target, $moved, $now, ' (ID ' . $keyValue . ' ist belegt)');
                    }

                    foreach ($config['refs'] as $refTable => $refColumn) {
                        $remap[$refTable][$refColumn][$keyValue] = $target;
                    }

                    continue;
                }

                [$lift, $keptColumns] = $this->liftColumns($table, $config['lift'], $keyValue, $row, $present[$keyValue]);

                foreach ($keptColumns as $column) {
                    $kept[] = ['table' => $table, 'key' => $keyValue, 'column' => $column];
                }

                if ($lift !== []) {
                    $actions[] = self::action(
                        self::KIND_LIFT,
                        $table,
                        $config['noun'] . ' „' . $keyValue . '“: ' . implode(', ', array_map(self::columnLabel(...), array_keys($lift))) . ' auf den neuen Stand bringen',
                        [[
                            'sql' => 'UPDATE `' . $table . '` SET ' . implode(', ', array_map(static fn (string $column): string => '`' . $column . '` = ?', array_keys($lift)))
                                . ' WHERE `' . $config['key'] . '` = ?',
                            'params' => array_merge(array_values($lift), [$keyValue]),
                        ]],
                    );
                }
            }
        }

        return [$actions, $kept];
    }

    /**
     * @param Snapshot $db
     *
     * @return array<array-key, Row>
     */
    private static function keyedDbRows(array $db, string $table, string $key): array
    {
        $keyed = [];

        foreach ($db['rows'][$table] ?? [] as $row) {
            $value = $row[$key] ?? null;

            if ($value !== null) {
                $keyed[$value] = $row;
            }
        }

        return $keyed;
    }

    /**
     * @param TableConfig $config
     * @param Row $row
     *
     * @return Action
     */
    private function insertAction(string $table, array $config, string $keyValue, array $row, int $now, string $suffix = ''): array
    {
        if ($config['auto'] !== '') {
            unset($row[$config['auto']]);
        }

        if ($table === 'pdl3_settings' && $keyValue === 'installed') {
            $row['wert'] = (string) $now;
        }

        $label = $config['noun'] . ' „' . $keyValue . '“';
        $name = $row['name'] ?? '';

        if ($name !== '' && $name !== $keyValue) {
            $label .= ' (' . $name . ')';
        }

        if ($table === 'pdl3_settings' && in_array($keyValue, self::SHOWN_VALUES, true)) {
            $label .= ' mit dem Wert „' . ($row['wert'] ?? '') . '“';
        }

        return self::action(self::KIND_INSERT_ROW, $table, $label . ' ergänzen' . $suffix, [self::insertQuery($table, $row)]);
    }

    /**
     * @param Row $row
     *
     * @return Query
     */
    private static function insertQuery(string $table, array $row): array
    {
        return [
            'sql' => 'INSERT INTO `' . $table . '` (' . implode(', ', array_map(static fn (string $column): string => '`' . $column . '`', array_keys($row)))
                . ') VALUES (' . implode(', ', array_fill(0, count($row), '?')) . ')',
            'params' => array_values($row),
        ];
    }

    /**
     * Spalten eines vorhandenen Eintrags, die gehoben werden (Wert = Stand
     * 3.5.0), und Spalten, die der Betreiber geändert hat.
     *
     * @param list<string> $columns
     * @param Row $schemaRow
     * @param Row $dbRow
     *
     * @return array{0: array<string, string|null>, 1: list<string>}
     */
    private function liftColumns(string $table, array $columns, string $keyValue, array $schemaRow, array $dbRow): array
    {
        $baseline = $this->defaults[$table][$keyValue] ?? null;
        $lift = [];
        $kept = [];

        foreach ($columns as $column) {
            if (!array_key_exists($column, $schemaRow) || !array_key_exists($column, $dbRow)) {
                continue;
            }

            $new = $schemaRow[$column];
            $current = $dbRow[$column];

            if (self::sameText($new, $current)) {
                continue;
            }

            if ($baseline !== null && array_key_exists($column, $baseline) && self::sameText($baseline[$column], $current)) {
                $lift[$column] = $new;
            } else {
                $kept[] = $column;
            }
        }

        return [$lift, $kept];
    }

    /**
     * Verständlicher Name einer Spalte, z. B. „Inhalt“ für wert.
     */
    public static function columnLabel(string $column): string
    {
        return self::COLUMN_LABELS[$column] ?? $column;
    }

    private static function unifyLineEndings(string $text): string
    {
        return str_replace(["\r\n", "\r"], "\n", $text);
    }

    /**
     * @param list<Query> $queries
     *
     * @return Action
     */
    private static function action(string $kind, string $table, string $label, array $queries): array
    {
        return ['kind' => $kind, 'table' => $table, 'label' => $label, 'queries' => $queries];
    }

    /**
     * Ausführungsreihenfolge wie KIND_LABELS. Innerhalb einer Art bleibt die
     * Reihenfolge erhalten.
     *
     * @param list<Action> $actions
     *
     * @return list<Action>
     */
    private static function sortActions(array $actions): array
    {
        $order = array_flip(array_keys(self::KIND_LABELS));
        $indexed = [];

        foreach ($actions as $index => $action) {
            $indexed[] = [$order[$action['kind']] ?? 99, $index, $action];
        }

        usort($indexed, static fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return array_map(static fn (array $item): array => $item[2], $indexed);
    }

    /**
     * @param Query $query
     */
    private static function execute(\mysqli $mysqli, array $query): void
    {
        if ($query['params'] === []) {
            $mysqli->query($query['sql']);

            return;
        }

        $stmt = $mysqli->prepare($query['sql']);

        if ($stmt === false) {
            throw new \mysqli_sql_exception('Die Anweisung konnte nicht vorbereitet werden.', $mysqli->errno);
        }

        $params = $query['params'];
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Typ am Anfang einer Spaltendefinition, z. B. „int unsigned“,
     * „varchar(128)“ oder „enum('Y','N')“.
     *
     * @return array{base: string, args: string, raw: string}|null
     */
    private static function parseType(string $definition): ?array
    {
        $pattern = "/^\\s*([A-Za-z]+)(?:\\(((?:[^()']|'(?:[^'\\\\]|\\\\.|'')*')*)\\))?(\\s+unsigned)?(\\s+zerofill)?/i";

        if (preg_match($pattern, $definition, $match) !== 1) {
            return null;
        }

        return ['base' => strtolower($match[1]), 'args' => $match[2] ?? '', 'raw' => $match[0]];
    }

    private static function typeOf(string $definition): string
    {
        $type = self::parseType($definition);

        return $type === null ? $definition : trim($type['raw']);
    }

    private static function quote(string $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "''"], $value) . "'";
    }

    /**
     * „1 Eintrag“ bzw. „3 Einträge“.
     */
    private static function entries(int $count): string
    {
        return $count . ($count === 1 ? ' Eintrag' : ' Einträge');
    }
}
