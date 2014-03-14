<?php

namespace CsvParser\Reader;

class Arrayreader implements ReaderInterface
{
    public static function read(\CsvParser\Parser $parser, $array)
    {
        return new \CsvParser\Csv($array);
    }
}
