<?php

/**
 * PowerDownload - Schemadatei lesen und zerlegen
 *
 * @package    PowerDownload
 * @license    MIT License
 */

declare(strict_types=1);

namespace PowerDownload\Installer;

/**
 * Liest pdl-inc/pdl3_schema.sql und zerlegt die Datei in Anweisungen,
 * Spaltendefinitionen und Datensätze.
 *
 * Web-Installer, update.php, Docker-Init und Tests verwenden dieselbe Datei.
 * Der Splitter beachtet Zeichenketten, Bezeichner und Kommentare: Semikolons
 * in HTML-Entitäten wie „&nbsp;“ innerhalb der Vorlagen trennen keine
 * Anweisung. split_query() aus pdl_functions.inc.php wird nicht verwendet,
 * weil es Escapes verdoppelt.
 *
 * @phpstan-type InsertData array{table: string, columns: list<string>, rows: list<list<string|null>>}
 */
final class Schema
{
    /**
     * Pfad relativ zum PowerDownload-Verzeichnis.
     */
    public const string FILENAME = 'pdl-inc/pdl3_schema.sql';

    public const string TABLE_PREFIX = 'pdl3_';

    /**
     * Anweisungen, die der Installer ausführen darf. Alles andere (DROP,
     * ALTER, DELETE, fremde Tabellen …) wird abgelehnt, bevor die Datenbank
     * berührt wird.
     */
    private const array ALLOWED = [
        '/^CREATE\s+TABLE\s+`?pdl3_[a-z0-9_]+`?\s*\(/i',
        '/^INSERT\s+INTO\s+`?pdl3_[a-z0-9_]+`?[\s(]/i',
        '/^SET\s+NAMES\s+utf8mb4$/i',
    ];

    /**
     * @throws \RuntimeException wenn die Datei nicht lesbar ist
     *
     * @return list<string>
     */
    public static function fromFile(string $path): array
    {
        $sql = is_file($path) && is_readable($path) ? file_get_contents($path) : false;

        if ($sql === false) {
            throw new \RuntimeException('Die Schemadatei ' . basename($path) . ' fehlt oder ist nicht lesbar. Bitte laden Sie sie erneut hoch.');
        }

        return self::split($sql);
    }

    /**
     * @return list<string>
     */
    public static function split(string $sql): array
    {
        if (str_starts_with($sql, "\xEF\xBB\xBF")) {
            $sql = substr($sql, 3);
        }

        $statements = [];
        $current = '';
        $length = strlen($sql);
        $pos = 0;

        while ($pos < $length) {
            $char = $sql[$pos];
            $commentEnd = self::commentEnd($sql, $pos);

            if ($commentEnd !== null) {
                $current .= ' ';
                $pos = $commentEnd;

                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $end = self::quotedEnd($sql, $pos);
                $current .= substr($sql, $pos, $end - $pos);
                $pos = $end;

                continue;
            }

            if ($char === ';') {
                self::addStatement($statements, $current);
                $current = '';
                ++$pos;

                continue;
            }

            $current .= $char;
            ++$pos;
        }

        self::addStatement($statements, $current);

        return $statements;
    }

    /**
     * Prüft, dass nur erlaubte Anweisungen enthalten sind, jede Tabelle genau
     * einmal angelegt wird und alle benötigten Tabellen dabei sind.
     *
     * @param list<string> $statements
     * @param list<string> $requiredTables Tabellen aus $sql_table (pdl_config.inc.php)
     *
     * @throws \UnexpectedValueException bei einer unerwarteten Anweisung
     */
    public static function assertInstallable(array $statements, array $requiredTables): void
    {
        $tables = self::tableNames($statements);

        if ($tables === []) {
            throw new \UnexpectedValueException('Die Schemadatei enthält keine Tabellen. Bitte laden Sie pdl-inc/pdl3_schema.sql erneut hoch.');
        }

        foreach ($statements as $statement) {
            if (!self::isAllowed($statement)) {
                throw new \UnexpectedValueException(
                    'Die Schemadatei enthält eine unerwartete Anweisung („' . self::excerpt($statement) . ' …“). '
                    . 'Bitte verwenden Sie die unveränderte Datei aus dem Release-Archiv.',
                );
            }
        }

        if (count($tables) !== count(array_unique($tables))) {
            throw new \UnexpectedValueException('Die Schemadatei legt eine Tabelle mehrfach an.');
        }

        $missing = array_diff($requiredTables, $tables);

        if ($missing !== []) {
            throw new \UnexpectedValueException('In der Schemadatei fehlen Tabellen: ' . implode(', ', $missing) . '. Bitte laden Sie pdl-inc/pdl3_schema.sql erneut hoch.');
        }
    }

    public static function isAllowed(string $statement): bool
    {
        foreach (self::ALLOWED as $pattern) {
            if (preg_match($pattern, $statement) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Name der Tabelle, die eine CREATE-TABLE-Anweisung anlegt, sonst null.
     */
    public static function createdTable(string $statement): ?string
    {
        return self::tableOf('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?/i', $statement);
    }

    /**
     * Name der Tabelle, in die eine INSERT-Anweisung schreibt, sonst null.
     */
    public static function insertedTable(string $statement): ?string
    {
        return self::tableOf('/^\s*INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?/i', $statement);
    }

    /**
     * @param list<string> $statements
     *
     * @return list<string>
     */
    public static function tableNames(array $statements): array
    {
        $tables = [];

        foreach ($statements as $statement) {
            $table = self::createdTable($statement);

            if ($table !== null) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    /**
     * Spalten einer CREATE-TABLE-Anweisung in der Reihenfolge der Datei:
     * Spaltenname => Definition ohne Namen, z. B. „varchar(128) NOT NULL DEFAULT ''“.
     * Schlüssel (PRIMARY KEY, KEY, UNIQUE KEY …) gehören nicht dazu.
     *
     * @return array<string, string>
     */
    public static function columns(string $createStatement): array
    {
        $open = strpos($createStatement, '(');
        $close = strrpos($createStatement, ')');

        if ($open === false || $close === false || $close <= $open) {
            return [];
        }

        $columns = [];

        foreach (self::splitTopLevel(substr($createStatement, $open + 1, $close - $open - 1), ',') as $part) {
            if (preg_match('/^`([^`]+)`\s+(.+)$/s', trim($part), $match) === 1) {
                $columns[$match[1]] = trim((string) preg_replace('/\s+/', ' ', $match[2]));
            }
        }

        return $columns;
    }

    /**
     * Zerlegt eine INSERT-Anweisung mit Spaltenliste in Tabelle, Spalten und
     * Werte. Zeichenketten werden wie von MySQL entschlüsselt (\n, \", '' …),
     * Zahlen bleiben Zeichenketten, NULL wird null.
     *
     * @throws \UnexpectedValueException wenn die Anweisung nicht dieser Form entspricht
     *
     * @return InsertData
     */
    public static function parseInsert(string $statement): array
    {
        if (preg_match('/^\s*INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?\s*\(([^)]*)\)\s*VALUES\s*/is', $statement, $match) !== 1) {
            throw new \UnexpectedValueException('Unerwartete INSERT-Anweisung („' . self::excerpt($statement) . ' …“): Spaltenliste fehlt.');
        }

        $columns = [];

        foreach (explode(',', $match[2]) as $column) {
            $columns[] = trim($column, " \t\r\n`");
        }

        $rows = [];
        $sql = $statement;
        $length = strlen($sql);
        $pos = strlen($match[0]);

        while (true) {
            $pos = self::skipSpace($sql, $pos);

            if (($sql[$pos] ?? '') !== '(') {
                throw new \UnexpectedValueException('Unerwartete INSERT-Anweisung für ' . $match[1] . ': „(“ erwartet.');
            }

            [$row, $pos] = self::parseTuple($sql, $pos + 1);

            if (count($row) !== count($columns)) {
                throw new \UnexpectedValueException('Unerwartete INSERT-Anweisung für ' . $match[1] . ': Anzahl der Werte passt nicht zur Spaltenliste.');
            }

            $rows[] = $row;
            $pos = self::skipSpace($sql, $pos);

            if ($pos >= $length) {
                break;
            }

            if ($sql[$pos] !== ',') {
                throw new \UnexpectedValueException('Unerwartete INSERT-Anweisung für ' . $match[1] . ': „,“ erwartet.');
            }

            ++$pos;
        }

        return ['table' => $match[1], 'columns' => $columns, 'rows' => $rows];
    }

    /**
     * Alle Datensätze aus den INSERT-Anweisungen, je Tabelle als Liste
     * assoziativer Arrays (Spalte => Wert).
     *
     * @param list<string> $statements
     *
     * @return array<string, list<array<string, string|null>>>
     */
    public static function rows(array $statements): array
    {
        $result = [];

        foreach ($statements as $statement) {
            if (self::insertedTable($statement) === null) {
                continue;
            }

            $insert = self::parseInsert($statement);

            foreach ($insert['rows'] as $values) {
                $result[$insert['table']][] = array_combine($insert['columns'], $values);
            }
        }

        return $result;
    }

    /**
     * Teilt eine Zeichenkette an $separator, aber nicht innerhalb von
     * Klammern oder Anführungszeichen.
     *
     * @return list<string>
     */
    public static function splitTopLevel(string $text, string $separator): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $length = strlen($text);
        $pos = 0;

        while ($pos < $length) {
            $char = $text[$pos];

            if ($char === "'" || $char === '"' || $char === '`') {
                $end = self::quotedEnd($text, $pos);
                $current .= substr($text, $pos, $end - $pos);
                $pos = $end;

                continue;
            }

            if ($char === '(') {
                ++$depth;
            } elseif ($char === ')') {
                --$depth;
            } elseif ($char === $separator && $depth === 0) {
                $parts[] = $current;
                $current = '';
                ++$pos;

                continue;
            }

            $current .= $char;
            ++$pos;
        }

        if (trim($current) !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    /**
     * Entschlüsselt eine MySQL-Zeichenkette ohne die umgebenden
     * Anführungszeichen (Backslash-Escapes und verdoppelte Anführungszeichen).
     */
    public static function unescape(string $body, string $quote): string
    {
        $result = '';
        $length = strlen($body);
        $pos = 0;

        while ($pos < $length) {
            $char = $body[$pos];

            if ($char === '\\' && $pos + 1 < $length) {
                $next = $body[$pos + 1];
                $result .= match ($next) {
                    '0' => "\0",
                    'b' => "\x08",
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'Z' => "\x1A",
                    '%', '_' => '\\' . $next,
                    default => $next,
                };
                $pos += 2;

                continue;
            }

            if ($char === $quote && ($body[$pos + 1] ?? '') === $quote) {
                $result .= $quote;
                $pos += 2;

                continue;
            }

            $result .= $char;
            ++$pos;
        }

        return $result;
    }

    /**
     * Liest die Werte einer Klammer ab $pos (direkt hinter „(“).
     *
     * @return array{0: list<string|null>, 1: int} Werte und Position hinter „)“
     */
    private static function parseTuple(string $sql, int $pos): array
    {
        $values = [];
        $length = strlen($sql);

        while (true) {
            $pos = self::skipSpace($sql, $pos);
            $char = $sql[$pos] ?? '';

            if ($char === "'" || $char === '"') {
                $end = self::quotedEnd($sql, $pos);
                $values[] = self::unescape(substr($sql, $pos + 1, $end - $pos - 2), $char);
                $pos = $end;
            } elseif (preg_match('/\G(-?\d+(?:\.\d+)?|NULL)(?![A-Za-z0-9_])/i', $sql, $match, 0, $pos) === 1) {
                $values[] = strtoupper($match[1]) === 'NULL' ? null : $match[1];
                $pos += strlen($match[1]);
            } else {
                throw new \UnexpectedValueException('Unerwarteter Wert in einer INSERT-Anweisung („' . self::excerpt(substr($sql, $pos)) . ' …“).');
            }

            $pos = self::skipSpace($sql, $pos);

            if ($pos >= $length) {
                throw new \UnexpectedValueException('Unvollständige INSERT-Anweisung.');
            }

            if ($sql[$pos] === ')') {
                return [$values, $pos + 1];
            }

            if ($sql[$pos] !== ',') {
                throw new \UnexpectedValueException('Unerwartetes Zeichen in einer INSERT-Anweisung („' . self::excerpt(substr($sql, $pos)) . ' …“).');
            }

            ++$pos;
        }
    }

    private static function skipSpace(string $sql, int $pos): int
    {
        $length = strlen($sql);

        while ($pos < $length && ctype_space($sql[$pos])) {
            ++$pos;
        }

        return $pos;
    }

    private static function excerpt(string $statement): string
    {
        $flat = (string) preg_replace('/\s+/', ' ', $statement);

        // Höchstens 40 Zeichen, ohne ein UTF-8-Zeichen zu zerschneiden (kein mbstring nötig).
        return preg_match('/^.{0,40}/su', $flat, $match) === 1 ? $match[0] : substr($flat, 0, 40);
    }

    private static function tableOf(string $pattern, string $statement): ?string
    {
        return preg_match($pattern, $statement, $match) === 1 ? $match[1] : null;
    }

    /**
     * @param list<string> $statements
     */
    private static function addStatement(array &$statements, string $statement): void
    {
        $statement = trim($statement);

        if ($statement !== '') {
            $statements[] = $statement;
        }
    }

    /**
     * Beginnt an $pos ein Kommentar, liefert die Position direkt dahinter
     * (bei Zeilenkommentaren: den Zeilenumbruch), sonst null.
     */
    private static function commentEnd(string $sql, int $pos): ?int
    {
        $char = $sql[$pos];
        $next = $sql[$pos + 1] ?? '';

        $isLineComment = $char === '#'
            || ($char === '-' && $next === '-' && in_array($sql[$pos + 2] ?? "\n", [' ', "\t", "\r", "\n"], true));

        if ($isLineComment) {
            $end = strpos($sql, "\n", $pos);

            return $end === false ? strlen($sql) : $end;
        }

        if ($char === '/' && $next === '*') {
            $end = strpos($sql, '*/', $pos + 2);

            return $end === false ? strlen($sql) : $end + 2;
        }

        return null;
    }

    /**
     * Position direkt hinter dem schließenden Anführungszeichen. Beachtet
     * Backslash-Escapes (nicht in Bezeichnern) und verdoppelte
     * Anführungszeichen.
     */
    private static function quotedEnd(string $sql, int $start): int
    {
        $quote = $sql[$start];
        $length = strlen($sql);
        $pos = $start + 1;

        while ($pos < $length) {
            $char = $sql[$pos];

            if ($char === '\\' && $quote !== '`') {
                $pos += 2;

                continue;
            }

            if ($char === $quote) {
                if (($sql[$pos + 1] ?? '') !== $quote) {
                    return $pos + 1;
                }
                $pos += 2;

                continue;
            }

            ++$pos;
        }

        return $length;
    }
}
