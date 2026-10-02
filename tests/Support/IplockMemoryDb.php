<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Support;

/**
 * Datenbank-Attrappe mit der Tabelle pdl3_iplock im Speicher.
 *
 * Versteht die einfachen Anweisungen aus pdl_locks.inc.php: INSERT, SELECT
 * (auch COUNT(*)) und DELETE mit Bedingungen der Form spalte='wert',
 * spalte<zahl und spalte>zahl, verbunden mit AND. So lassen sich ganze
 * Abläufe (Fehlversuche, Bewertungen im Vereins-WLAN) durchspielen.
 */
class IplockMemoryDb extends MockDbHandler
{
    /** @var list<array{ip: string, time: int, file_id: int, user_id: int, art: string}> */
    public array $rows = [];

    /** @var list<string> */
    public array $queries = [];

    public function sql_query(string $query): MockResult
    {
        $this->queries[] = $query;
        $this->querys++;

        if (preg_match("/^INSERT INTO pdl3_iplock \\(ip,time,file_id,user_id,art\\) VALUES \\('(.*)','(\\d+)','(\\d+)','(\\d+)','(\\w+)'\\)$/", $query, $m) === 1) {
            $this->rows[] = ['ip' => stripslashes($m[1]), 'time' => (int) $m[2], 'file_id' => (int) $m[3], 'user_id' => (int) $m[4], 'art' => $m[5]];
            return new MockResult([]);
        }

        if (preg_match('/^(SELECT (.+?) FROM|DELETE FROM) pdl3_iplock WHERE (.+?)( LIMIT 1)?$/', $query, $m) === 1) {
            $where = $m[3];
            if (str_starts_with($m[1], 'DELETE')) {
                $this->rows = array_values(array_filter($this->rows, fn (array $row): bool => !$this->matches($row, $where)));
                return new MockResult([]);
            }
            $found = array_values(array_filter($this->rows, fn (array $row): bool => $this->matches($row, $where)));
            if ($m[2] === 'COUNT(*) AS c') {
                return new MockResult([['c' => count($found)]]);
            }
            return new MockResult(($m[4] ?? '') !== '' ? array_slice($found, 0, 1) : $found);
        }

        throw new \LogicException('Unerwartete Abfrage: ' . $query);
    }

    /**
     * Anzahl der Einträge einer Art (für Prüfungen in den Tests).
     */
    public function count(string $art): int
    {
        return count(array_filter($this->rows, static fn (array $row): bool => $row['art'] === $art));
    }

    /**
     * @param array{ip: string, time: int, file_id: int, user_id: int, art: string} $row
     */
    private function matches(array $row, string $where): bool
    {
        foreach (explode(' AND ', $where) as $condition) {
            if (preg_match("/^(ip|time|file_id|user_id|art)([=<>])'?(.*?)'?$/", $condition, $c) !== 1) {
                throw new \LogicException('Unerwartete Bedingung: ' . $condition);
            }
            $value = $row[$c[1]];
            $expected = stripslashes($c[3]);
            $ok = match ($c[2]) {
                '=' => (string) $value === $expected,
                '<' => (int) $value < (int) $expected,
                default => (int) $value > (int) $expected,
            };
            if (!$ok) {
                return false;
            }
        }
        return true;
    }
}
