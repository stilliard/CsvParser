<?php

namespace CsvParser\Middleware;

/**
 * Middleware trait to escape cells, such prevent formatting issues or protect against formula injection attacks
 */
trait EscapeMiddlewareTrait
{
    protected string $writePattern;
    protected string $readPattern;
    protected string $escapeChar = "'";

    protected function needsEscaping(string $cell): bool
    {
        return !! preg_match($this->writePattern, $cell);
    }
    protected function escape(string $cell): string
    {
        return $this->escapeChar . $cell;
    }

    protected function isEscaped(string $cell): bool
    {
        return !! preg_match($this->readPattern, $cell);
    }
    protected function unescape(string $cell): string
    {
        return substr($cell, 1);
    }

    // when writing out a row, escape any cells that could be formula injections
    public function write(array $row, array $context): array
    {
        return array_map(fn ($cell) => $this->needsEscaping($cell) ? $this->escape($cell) : $cell, $row);
    }

    // when reading in a row, undo any escaping we did on write
    public function read(array $row, array $context): array
    {
        return array_map(fn ($cell) => $this->isEscaped($cell) ? $this->unescape($cell) : $cell, $row);
    }
}
