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

        $escape = fn ($value) =>
            str_replace($parser->fieldEnclosure, $parser->fieldEnclosure . $parser->fieldEnclosure, $value);

        fwrite($resource, $parser->fieldEnclosure . implode($parser->fieldEnclosure . $parser->fieldDelimiter . $parser->fieldEnclosure, array_map($escape, $keys)) . $parser->fieldEnclosure . $parser->lineDelimiter);

        while ($row = $callback()) {
            fwrite($resource, $parser->fieldEnclosure . implode($parser->fieldEnclosure . $parser->fieldDelimiter . $parser->fieldEnclosure, array_map($escape, $row)) . $parser->fieldEnclosure . $parser->lineDelimiter);
        }
    }
}
