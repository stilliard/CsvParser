<?php

namespace CsvParser\Middleware;

interface StringReaderMiddlewareInterface
{
    public function __construct(array $options = []);
    public function read(array $row, array $context): array;
}
