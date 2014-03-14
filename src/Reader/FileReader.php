<?php

namespace CsvParser\Reader;

class FileReader extends AbstractReader
{
    public static function read(\CsvParser\Parser $parser, $file)
    {
        $contents = file_get_contents($file);
        return $parser->fromString($contents);
    }
}
