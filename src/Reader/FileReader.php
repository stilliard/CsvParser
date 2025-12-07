<?php

namespace CsvParser\Reader;

use CsvParser\Encoding\EncodingConverterInterface;
use Exception;

class FileReader implements ReaderInterface
{
    protected static $options = [
        'encodingConverter' => null, // EncodingConverterInterface instance or null for no conversion
    ];

    public static function setDefaultOptions(array $options)
    {
        self::$options = array_merge(self::$options, $options);
    }

    public static function read(\CsvParser\Parser $parser, $file, array $options = [])
    {
        $options = array_merge(self::$options, $options);

        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new Exception("Could not open file: " . basename($file));
        }

        // Use encoding converter if provided
        if ($options['encodingConverter'] instanceof EncodingConverterInterface) {
            $contents = $options['encodingConverter']->convert($contents);
        }

        return $parser->fromString($contents);
    }
}
