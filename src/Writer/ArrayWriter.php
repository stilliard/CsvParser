<?php

namespace CsvParser\Writer;

class Arraywriter extends AbstractWriter
{
    public static function write(\CsvParser\Parser $parser, \CsvParser\Csv $csv)
    {
        return $csv->getData();
    }
}
