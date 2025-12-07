<?php

namespace CsvParser\Writer;

class StringWriter implements WriterInterface
{
    protected static function isAssoc(array $array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    public static function write(\CsvParser\Parser $parser, \CsvParser\Csv $csv)
    {
        $data = $csv->getData();
        $data = $parser->applyStringWriterMiddleware($data);

        if ($data && !empty($data)) {
            $firstLine = current($data);
            $output = [];
            // only write header if the first line is an associative array
            if (self::isAssoc($firstLine)) {
                $output[] = implode($parser->fieldDelimiter, array_map(function ($value) use ($parser) {
                    return $parser->fieldEnclosure . str_replace($parser->fieldEnclosure, $parser->fieldEnclosure.$parser->fieldEnclosure, $value) . $parser->fieldEnclosure;
                }, array_keys($firstLine)));
            }

            foreach ($data as $line) {
                $output[] = implode($parser->fieldDelimiter, array_map(function ($value) use ($parser) {
                    return $parser->fieldEnclosure . str_replace($parser->fieldEnclosure, $parser->fieldEnclosure.$parser->fieldEnclosure, $value) . $parser->fieldEnclosure;
                }, $line));
            }

            return implode($parser->lineDelimiter, $output);
        } else {
            return '';
        }
    }
}
