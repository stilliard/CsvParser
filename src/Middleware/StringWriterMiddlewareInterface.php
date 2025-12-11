<?php

namespace CsvParser\Middleware;

interface StringWriterMiddlewareInterface
{
    public function __construct(array $options = []);
    public function write(array $row, array $context): array;
}
