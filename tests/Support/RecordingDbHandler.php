<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Support;

/**
 * MockDbHandler, der zusätzlich alle SQL-Anweisungen mitschreibt.
 */
class RecordingDbHandler extends MockDbHandler
{
    /** @var list<string> */
    public array $queries = [];

    public function sql_query(string $query): MockResult
    {
        $this->queries[] = $query;
        return parent::sql_query($query);
    }

    /**
     * Alle mitgeschriebenen Anweisungen, die $needle enthalten.
     *
     * @return list<string>
     */
    public function queriesContaining(string $needle): array
    {
        return array_values(array_filter($this->queries, static fn (string $q): bool => str_contains($q, $needle)));
    }
}
