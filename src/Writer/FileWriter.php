<?php

namespace CsvParser\Writer;

class FileWriter extends AbstractWriter
{
    public static function write(\CsvParser\Parser $parser, \CsvParser\Csv $csv)
    {
        $file = $parser->toString($csv);
        return file_put_contents($file);
    }
}
