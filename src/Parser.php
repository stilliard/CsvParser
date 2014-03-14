<?php

namespace CsvParser;

class Parser
{
    public $fieldDelimiter = ',';
    public $fieldEnclosure = '"';
    public $lineDelimiter = "\n";

    public function __construct($fieldDelimiter = null, $fieldEnclosure = null, $lineDelimiter = null)
    {
        if ($fieldDelimiter) {
            $this->fieldDelimiter = $fieldDelimiter;
        }
        if ($fieldEnclosure) {
            $this->fieldEnclosure = $fieldEnclosure;
        }
        if ($lineDelimiter) {
            $this->lineDelimiter = $lineDelimiter;
        }
    }

    /* Readers */

    public function fromString($string)
    {
        return Reader\StringReader::read($this, $string);
    }

    public function fromArray($array)
    {
        return Reader\ArrayReader::read($this, $array);
    }

    public function fromFile($file)
    {
        return Reader\FileReader::read($this, $file);
    }

    /* Writers */

    public function toString(Csv $csv)
    {
        return Writer\StringWriter::write($this, $csv);
    }

    public function toArray(Csv $csv)
    {
        return Writer\ArrayWriter::write($this, $csv);
    }

    public function toFile(Csv $csv)
    {
        return Writer\FileWriter::write($this, $csv);
    }

    /* Special writers */

    public function toChunks(Csv $csv, $size=1000)
    {
        return Writer\ChunksWriter::write($this, $csv, $size);
    }

}
