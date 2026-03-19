<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Support;

class MockDbHandler
{
    public int $querys = 0;
    /** @var MockResult[] */
    private array $queryResults = [];
    private int $queryIndex = 0;
    public ?object $handler = null;

    public function addResult(array $rows, int $numFields = 0): self
    {
        $this->queryResults[] = new MockResult($rows, $numFields);
        return $this;
    }

    public function sql_query(string $query): MockResult
    {
        $this->querys++;
        $index = $this->queryIndex++;
        if (isset($this->queryResults[$index])) {
            return $this->queryResults[$index];
        }
        return new MockResult([]);
    }

    public function sql_fetch_array(MockResult|bool|null $result): ?array
    {
        if ($result instanceof MockResult) {
            return $result->fetch();
        }
        return null;
    }

    public function sql_num_rows(MockResult|bool|null $result): int
    {
        if ($result instanceof MockResult) {
            return $result->numRows();
        }
        return 0;
    }

    public function sql_num_fields(MockResult|bool|null $result): int
    {
        if ($result instanceof MockResult) {
            return $result->numFields();
        }
        return 0;
    }

    public function sql_escape_string(string $string): string
    {
        return addslashes($string);
    }

    public function sql_escape_int(mixed $value): int
    {
        return (int) $value;
    }

    public function sql_insert_id(): int
    {
        return 0;
    }

    public function sql_close(): void
    {
    }
}
