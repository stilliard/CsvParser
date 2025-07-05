<?php

namespace CsvParser\Writer;

use CsvParser\Parser;

class StreamWriter
{
    public static function write(Parser $parser, $resource, $keys, $callback)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Invalid resource provided');
        }

        $escape = fn (?string $value) =>
            str_replace($parser->fieldEnclosure, $parser->fieldEnclosure . $parser->fieldEnclosure, $value ?? '');

        $writeRow = fn (array $row) =>
            fwrite($resource, $parser->fieldEnclosure . implode($parser->fieldEnclosure . $parser->fieldDelimiter . $parser->fieldEnclosure, array_map($escape, $row)) . $parser->fieldEnclosure . $parser->lineDelimiter);

        // Write header
        $writeRow($keys);

        // get first row or generator
        $data = $callback();

        // Case 1: Generator or iterable returned
        if (is_iterable($data) && ! is_array($data)) {
            foreach ($data as $row) {
                $writeRow($row);
            }
        }
        // Case 2: Pull-style row returned; loop until false/null
        else {
            while ($data) {
                $writeRow($data);
                $data = $callback();
            }
        }
    }
}
