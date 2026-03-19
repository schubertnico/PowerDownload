<?php

declare(strict_types=1);

namespace PowerDownload\Tests\Support;

class MockResult
{
    private array $rows;
    private int $index = 0;
    private int $numFields;

    public function __construct(array $rows, int $numFields = 0)
    {
        $this->rows = $rows;
        $this->numFields = $numFields;
    }

    public function fetch(): ?array
    {
        if ($this->index < count($this->rows)) {
            return $this->rows[$this->index++];
        }
        return null;
    }

    public function numRows(): int
    {
        return count($this->rows);
    }

    public function numFields(): int
    {
        return $this->numFields;
    }

    public function reset(): void
    {
        $this->index = 0;
    }
}
