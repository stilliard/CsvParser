<?php

namespace CsvParser\Middleware;

use CsvParser\Utils;

/**
 * Trait for middleware that operates on specific fields
 */
trait FieldBasedMiddlewareTrait
{
    protected array $fields = [];

    /**
     * Resolve the array key for a field name, handling both associative and indexed arrays
     *
     * @return string|int|null The key to use, or null if field not found
     */
    protected function resolveKey(string $field, array $row, array $context)
    {
        if (Utils::isAssoc($row)) {
            return $field;
        }

        $headings = $context['headings'] ?? [];
        $index = array_search($field, $headings);
        return $index !== false ? $index : null;
    }

    /**
     * Process each configured field in a row using a callback
     *
     * @param array $row The row to process
     * @param array $context The context containing headings
     * @param callable $callback Function to call for each field: fn($value, $key) => $newValue
     * @return array The modified row
     */
    protected function processFields(array $row, array $context, callable $callback): array
    {
        foreach ($this->fields as $field) {
            $key = $this->resolveKey($field, $row, $context);
            if ($key !== null && isset($row[$key])) {
                $row[$key] = $callback($row[$key], $key);
            }
        }
        return $row;
    }
}
