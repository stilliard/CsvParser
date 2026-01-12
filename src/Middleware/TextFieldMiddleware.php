<?php

namespace CsvParser\Middleware;

/**
 * Mark fields provided as text to prevent auto-formatting in spreadsheet applications
 * Similar to DatetimeMiddleware but for a given list of fields
 */
class TextFieldMiddleware implements StringWriterMiddlewareInterface, StringReaderMiddlewareInterface
{
    use FieldBasedMiddlewareTrait;

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
        return $this->processFields($row, $context, fn($value)
            => $value ? $this->escapeChar . $value : $value);
    }

    public function read(array $row, array $context): array
    {
        return $this->processFields($row, $context, fn($value)
            => (isset($value[0]) && $value[0] === $this->escapeChar) ? substr($value, 1) : $value);
    }
}
