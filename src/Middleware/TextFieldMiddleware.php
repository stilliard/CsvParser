<?php

namespace CsvParser\Middleware;

/**
 * Mark fields provided as text to prevent auto-formatting in spreadsheet applications
 * Similar to DatetimeMiddleware but for a given list of fields
 */
class TextFieldMiddleware implements StringWriterMiddlewareInterface, StringReaderMiddlewareInterface
{
    protected array $fields = [];
    protected string $escapeChar = "'";

    public function __construct(array $options = [])
    {
        if (isset($options['fields']) && is_array($options['fields'])) {
            $this->fields = $options['fields'];
        }
        if (isset($options['escapeChar'])) {
            $this->escapeChar = $options['escapeChar'];
        }
    }

    public function write(array $row, array $context): array
    {
        foreach ($this->fields as $field) {
            if (isset($row[$field]) && $row[$field]) {
                $row[$field] = $this->escapeChar . $row[$field];
            }
        }
        return $row;
    }

    public function read(array $row, array $context): array
    {
        foreach ($this->fields as $field) {
            if (isset($row[$field]) && substr($row[$field], 0, 1) === $this->escapeChar) {
                $row[$field] = substr($row[$field], 1);
            }
        }
        return $row;
    }
}
