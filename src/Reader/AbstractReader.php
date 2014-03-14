<?php

namespace CsvParser\Reader;

abstract class AbstractReader
{
    abstract public static function read(\CsvParser\Parser $parser, $val);
}
