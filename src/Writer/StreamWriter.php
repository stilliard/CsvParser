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

        fwrite($resource, $parser->fieldEnclosure . implode($parser->fieldEnclosure . $parser->fieldDelimiter . $parser->fieldEnclosure, $keys) . $parser->fieldEnclosure . $parser->lineDelimiter);

        while ($row = $callback()) {
            fwrite($resource, $parser->fieldEnclosure . implode($parser->fieldEnclosure . $parser->fieldDelimiter . $parser->fieldEnclosure, $row) . $parser->fieldEnclosure . $parser->lineDelimiter);
        }
    }
}
