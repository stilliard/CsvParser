<?php

namespace CsvParser\Writer;

class StringWriter extends AbstractWriter
{
    public static function write(\CsvParser\Parser $parser, \CsvParser\Csv $csv)
    {
        $data = $csv->getData();

        $output = [
            implode($parser->fieldDelimiter, array_map(function ($value) use ($parser) {
                return $parser->fieldEnclosure . $value . $parser->fieldEnclosure;
            }, array_keys($data[1])))
        ];

        foreach ($data as $line) {
            $output[] = implode($parser->fieldDelimiter, array_map(function ($value) use ($parser) {
                return $parser->fieldEnclosure . $value . $parser->fieldEnclosure;
            }, $line));
        }
        
        return implode($parser->lineDelimiter, $output);
    }
}
