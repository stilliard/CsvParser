<?php

namespace CsvParser;

class Parser
{
    public $fieldDelimiter = ',';
    public $fieldEnclosure = '"';
    public $lineDelimiter = "\n";

    public function __construct($fieldDelimiter = null, $fieldEnclosure = null, $lineDelimiter = null)
    {
        if ( ! is_null($fieldDelimiter)) {
            $this->fieldDelimiter = $fieldDelimiter;
        }
        if ( ! is_null($fieldEnclosure)) {
            $this->fieldEnclosure = $fieldEnclosure;
        }
        if ( ! is_null($lineDelimiter)) {
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

    public function fromFile($file, $fixEncoding = true)
    {
        Reader\FileReader::$fixEncoding = $fixEncoding;
        return Reader\FileReader::read($this, $file);
    }

    protected static function instanceFromOptions(?array $options = null)
    {
        $options = $options ?? [];
        return new static(
            $options['fieldDelimiter'] ?? null,
            $options['fieldEnclosure'] ?? null,
            $options['lineDelimiter'] ?? null
        );
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

    public function toFile(Csv $csv, $filename)
    {
        return Writer\FileWriter::write($this, $csv, $filename);
    }

    /* Special writers */

    public function toChunks(Csv $csv, $size=1000)
    {
        return Writer\ChunksWriter::write($this, $csv, $size);
    }

    public function toStream($resource, $keys, $callback)
    {
        return Writer\StreamWriter::write($this, $resource, $keys, $callback);
    }

    /* Static helpers */

    public static function stream($file, ?array $options = null)
    {
        $parser = static::instanceFromOptions($options);
        return Reader\StreamReader::read($parser, $file);
    }

    public static function write($data, $filename, ?array $options = null)
    {
        $parser = static::instanceFromOptions($options);
        return $parser->toFile(new Csv($data), $filename);
    }

    public static function writeStream($resource, $keys, $callback, ?array $options = null)
    {
        $parser = static::instanceFromOptions($options);
        return $parser->toStream($resource, $keys, $callback);
    }
}
