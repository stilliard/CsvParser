<?php

namespace CsvParser\Reader;

use Exception;
use CsvParser\Parser;

class StreamReader implements ReaderInterface
{
    public static function read(Parser $parser, $file)
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            throw new Exception("Could not open file: $file");
        }
        // Skip a UTF-8 BOM (Excel adds one) before parsing.
        // Stripping it from the first heading afterwards is too late when that heading is quoted,
        // as fgetcsv sees the BOM before the opening quote and returns the quotes as literal text.
        if (stream_get_meta_data($handle)['seekable'] && fread($handle, 3) !== Parser::BOM) {
            rewind($handle);
        }

        $headings = fgetcsv($handle, 0, $parser->fieldDelimiter, $parser->fieldEnclosure);

        // fallback for non-seekable streams, works for unquoted headings only
        if ($headings && isset($headings[0])) {
            $headings[0] = Parser::stripBom($headings[0]);
        }

        $index = 0;
        while (($row = fgetcsv($handle, 0, $parser->fieldDelimiter, $parser->fieldEnclosure)) !== false) {
            $combined = array_combine($headings, $row);
            $combined = $parser->applyStringReaderMiddlewareToRow($combined, $index, $headings);
            yield $combined;
            $index++;
        }
    }
}
