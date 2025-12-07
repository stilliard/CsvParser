<?php

namespace CsvParser\Encoding;

interface EncodingConverterInterface
{
    /**
     * Convert content to UTF-8
     *
     * @param string $contents Raw file contents in any encoding
     * @return string UTF-8 encoded content
     */
    public function convert(string $contents): string;
}
