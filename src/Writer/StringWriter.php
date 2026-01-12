<?php

namespace CsvParser\Writer;

use CsvParser\Utils;

class StringWriter implements WriterInterface
{
    public static function write(\CsvParser\Parser $parser, \CsvParser\Csv $csv)
    {
        $data = $csv->getData();

        if ($data && !empty($data)) {
            $firstLine = current($data);
            $headers = [];
            $output = [];
            // only write header if the first line is an associative array
            if (Utils::isAssoc($firstLine)) {
                $headers = array_keys($firstLine);
                $output[] = implode($parser->fieldDelimiter, array_map(function ($value) use ($parser) {
                    return $parser->fieldEnclosure . str_replace($parser->fieldEnclosure, $parser->fieldEnclosure.$parser->fieldEnclosure, $value) . $parser->fieldEnclosure;
                }, $headers));
            }

            $data = $parser->applyStringWriterMiddleware($data, $headers);

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
