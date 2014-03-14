<?php

namespace CsvParser\Writer;

class Arraywriter implements WriterInterface
{
    public static function write(\CsvParser\Parser $parser, \CsvParser\Csv $csv)
    {
        return $csv->getData();
    }
}
