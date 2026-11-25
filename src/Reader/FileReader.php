<?php

namespace CsvParser\Reader;

class FileReader implements ReaderInterface
{
    public static function read(\CsvParser\Parser $parser, $file)
    {
        return $parser->fromString(file_get_contents($file));
    }
}
