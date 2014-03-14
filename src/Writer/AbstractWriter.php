<?php

namespace CsvParser\Writer;

abstract class AbstractWriter
{
    abstract public static function write(\CsvParser\Parser $parser, \CsvParser\Csv $csv);
}
