<?php

namespace CsvParser\Middleware;

/**
 * Trim whitespace from specified fields
 */
class TrimFieldMiddleware implements StringWriterMiddlewareInterface, StringReaderMiddlewareInterface
{
    use FieldBasedMiddlewareTrait;

    protected string $characters = " \t\n\r\0\x0B";

    public function __construct(array $options = [])
    {
        if (isset($options['fields']) && is_array($options['fields'])) {
            $this->fields = $options['fields'];
        }
        if (isset($options['characters'])) {
            $this->characters = $options['characters'];
        }
    }

    public function write(array $row, array $context): array
    {
        return $this->processFields($row, $context, fn($value) => trim($value, $this->characters));
    }

    public function read(array $row, array $context): array
    {
        return $this->processFields($row, $context, fn($value) => trim($value, $this->characters));
    }
}
