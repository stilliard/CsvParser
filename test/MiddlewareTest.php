<?php

use CsvParser\Parser;
use CsvParser\Middleware\FormulaInjectionMiddleware;
use CsvParser\Middleware\DatetimeMiddleware;

class MiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function testBasicMiddleware()
    {
        $parser = new Parser();
        $parser->addMiddleware(new FormulaInjectionMiddleware);
        $parser->addMiddleware(new DatetimeMiddleware);

        $orig = [
            ['Name' => 'Alice', 'Comment' => '=HYPERLINK("http://malicious.com","Click me!")', 'Date' => '2024-01-01'],
            ['Name' => 'Bob', 'Comment' => 'Hello world!', 'Date' => '2024-02-15 12:30:00'],
        ];
        $csv = $parser->fromArray($orig);

        // check that the formula injection was escaped on write
        $safe = <<<CSV
        "Name","Comment","Date"
        "Alice","'=HYPERLINK(""http://malicious.com"",""Click me!"")","'2024-01-01"
        "Bob","Hello world!","'2024-02-15 12:30:00"
        CSV;
        $this->assertSame($safe, $parser->toString($csv));

        // check that the formula injection was unescaped on read
        $csv2 = $parser->fromString($safe);
        $this->assertSame($orig, $csv2->getData());
    }
}
