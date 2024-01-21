<?php

namespace CsvParser\Reader;

use Exception;
use CsvParser\Parser;

class StreamReader implements ReaderInterface
{
    public static function read(Parser $parser, $file)
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            throw new Exception("Could not open file: $file");
        }
        $headings = fgetcsv($handle, 0, $parser->fieldDelimiter, $parser->fieldEnclosure);
        while (($row = fgetcsv($handle, 0, $parser->fieldDelimiter, $parser->fieldEnclosure)) !== false) {
            yield array_combine($headings, $row);
        }
    }
}
