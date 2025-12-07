<?php

namespace CsvParser;

use CsvParser\Middleware\StringReaderMiddlewareInterface;
use CsvParser\Middleware\StringWriterMiddlewareInterface;

class Parser
{
    public string $fieldDelimiter = ',';
    public string $fieldEnclosure = '"';
    public string $lineDelimiter = "\n";

    // Middleware stacks
    public array $middleware = [];

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

    public function fromFile($file)
    {
        return Reader\FileReader::read($this, $file);
    }

    public function fromStream($file)
    {
        return Reader\StreamReader::read($this, $file);
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
        return $parser->fromStream($file);
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

    // Middleware
    public function addMiddleware($middleware)
    {
        $this->middleware[] = $middleware;
    }

    protected function getMiddlewareByType($interface)
    {
        $filtered = [];
        foreach ($this->middleware as $middleware) {
            if ($middleware instanceof $interface) {
                $filtered[] = $middleware;
            }
        }
        return $filtered;
    }

    // String reader and writer middleware, applies to all string based readers and writers (string, file & stream)
    public function applyStringReaderMiddleware(?array $data): ?array
    {
        if (empty($data)) {
            return $data;
        }

        $stringReaderMiddleware = $this->getMiddlewareByType(StringReaderMiddlewareInterface::class);
        if (empty($stringReaderMiddleware)) {
            return $data;
        }
        foreach ($data as $index => $row) {
            foreach ($stringReaderMiddleware as $middleware) {
                $row = $middleware->read($row, ['index' => $index]);
            }
            $data[$index] = $row;
        }
        return $data;
    }

    public function applyStringWriterMiddleware(?array $data): ?array
    {
        if (empty($data)) {
            return $data;
        }

        $stringWriterMiddleware = $this->getMiddlewareByType(StringWriterMiddlewareInterface::class);
        if (empty($stringWriterMiddleware)) {
            return $data;
        }
        foreach ($data as $index => $row) {
            foreach ($stringWriterMiddleware as $middleware) {
                $row = $middleware->write($row, ['index' => $index]);
            }
            $data[$index] = $row;
        }
        return $data;
    }
}
