<?php

namespace CsvParser\Reader;

class StringReader implements ReaderInterface
{
    public static function read(\CsvParser\Parser $parser, $string)
    {
        $data = array();

        // shorten some vars for use later
        $d = $parser->fieldDelimiter;
        $e = $parser->fieldEnclosure;
        $l = $parser->lineDelimiter;

        // get headings and body (if \n line feeds, also support reading on carriage returns)
        list($headings, $body) = $l=="\n" ? preg_split('/[\n\r]/', $string, 2) : explode($l, $string, 2);

        // format array of headings/keys
        $headings = str_getcsv($headings, $d, $e);
        $numDelims = count($headings) -1; // number of field delims to find per line

        // tricky bit of regex, optionally matching the text enclosure (ref as \2 after first match),
        // and catches any content inside this enclosure, even if that would be the field or line delim
        // then repeating this match followed by the field delim for the number of columns we need (minus 1) and then the match again this time without the field delim
        // then splits the lines only when not in the enclosure
        preg_match_all('/(('. $e .')?[\s\S]*?\2?'. $d .'){'. $numDelims .'}\2?[\s\S]*?\2?('. ($l=="\n" ? '\n|\r' : $l) .'|$)/', $body, $lines);

        // any lines found? loop them
        if ( ! empty($lines) && ! empty($lines[0])) {
            foreach ($lines[0] as $i => $line) {
                if ($line==='') {
                    continue; // blank line...
                }
                $fields = str_getcsv($line, $d, $e);
                $data[$i] = array();
                // loop the headings to map to columns
                foreach ($headings as $j => $heading) {
                    $field = isset($fields[$j]) ? $fields[$j] : '';
                    $data[$i][$heading] = $field;
                }
            }
        }

        return new \CsvParser\Csv($data);
    }
}
