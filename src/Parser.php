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

    public function getMiddlewareByType($interface): array
    {
        $filtered = [];
        foreach ($this->middleware as $middleware) {
            if ($middleware instanceof $interface) {
                $filtered[] = $middleware;
            }
        }
        return $filtered;
    }

    // Generic middleware application methods
    protected function applyMiddleware(?array $data, string $interfaceClass, string $method): ?array
    {
        if (empty($data)) {
            return $data;
        }

        $middlewares = $this->getMiddlewareByType($interfaceClass);
        foreach ($data as $index => $row) {
            foreach ($middlewares as $middleware) {
                $row = $middleware->$method($row, ['index' => $index]);
            }
            $data[$index] = $row;
        }
        return $data;
    }

    protected function applyMiddlewareToRow(array $row, int $index, string $interfaceClass, string $method): array
    {
        $middlewares = $this->getMiddlewareByType($interfaceClass);
        foreach ($middlewares as $middleware) {
            $row = $middleware->$method($row, ['index' => $index]);
        }
        return $row;
    }

    // String reader and writer middleware convenience methods
    public function applyStringReaderMiddleware(?array $data): ?array
    {
        return $this->applyMiddleware($data, StringReaderMiddlewareInterface::class, 'read');
    }

    public function applyStringWriterMiddleware(?array $data): ?array
    {
        return $this->applyMiddleware($data, StringWriterMiddlewareInterface::class, 'write');
    }

    public function applyStringReaderMiddlewareToRow(array $row, int $index): array
    {
        return $this->applyMiddlewareToRow($row, $index, StringReaderMiddlewareInterface::class, 'read');
    }

    public function applyStringWriterMiddlewareToRow(array $row, int $index): array
    {
        return $this->applyMiddlewareToRow($row, $index, StringWriterMiddlewareInterface::class, 'write');
    }
}
