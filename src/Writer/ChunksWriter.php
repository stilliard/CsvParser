<?php

namespace CsvParser\Writer;

class ChunksWriter implements WriterInterface
{
    public static function write(\CsvParser\Parser $parser, \CsvParser\Csv $csv, $size)
    {
        $data = $csv->getData();
        $chunks = array_chunk($data, $size, true);
        $end = [];
        foreach ($chunks as $chunk) {
            $end[] = new \CsvParser\Csv($chunk);
        }
        return $end;
    }
}
