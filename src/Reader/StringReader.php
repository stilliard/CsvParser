<?php

namespace CsvParser\Reader;

class StringReader extends AbstractReader
{
    public static function read(\CsvParser\Parser $parser, $string)
    {
        $data = [];
        $lines = explode($parser->lineDelimiter, $string);
        foreach ($lines as $i => $line) {
            if ($line==='') {
                continue; // blank line...
            }
            $fields = str_getcsv($line, $parser->fieldDelimiter, $parser->fieldEnclosure);
            if ($i===0) {
                // first line, column headings
                $headings = $fields;
            }
            else {
                $data[$i-1] = [];
                foreach ($headings as $j => $heading) {
                    $data[$i-1][$heading] = $fields[$j];
                }
            }
        }
        return new \CsvParser\Csv($data);
    }
}
