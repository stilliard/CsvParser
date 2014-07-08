<?php

namespace CsvParser\Reader;

class StringReader implements ReaderInterface
{
    public static function read(\CsvParser\Parser $parser, $string)
    {
        $data = array();
        $headings = array();

        // split lines
        // regex from here: https://bugs.php.net/bug.php?id=55763
        $lines = preg_split('/[\r\n]{1,2}(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/', $string);

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
                $data[$i-1] = array();
                foreach ($headings as $j => $heading) {
                    $field = isset($fields[$j]) ? $fields[$j] : '';
                    $data[$i-1][$heading] = $field;
                }
            }
        }
        return new \CsvParser\Csv($data);
    }
}
