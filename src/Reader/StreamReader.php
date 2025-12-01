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
            throw new Exception("Could not open file: " . basename($file));
        }
        $headings = fgetcsv($handle, 0, $parser->fieldDelimiter, $parser->fieldEnclosure);

        // handle removing BOM if present
        if ($headings && isset($headings[0])) {
            $headings[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headings[0]); // remove UTF-8 BOM
        }

        while (($row = fgetcsv($handle, 0, $parser->fieldDelimiter, $parser->fieldEnclosure)) !== false) {
            yield array_combine($headings, $row);
        }
    }
}
