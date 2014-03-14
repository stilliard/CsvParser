<?php

namespace CsvParser\Reader;

class Arrayreader extends AbstractReader
{
    public static function read(\CsvParser\Parser $parser, $array)
    {
        return new \CsvParser\Csv($array);
    }
}
