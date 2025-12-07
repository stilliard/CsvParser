<?php

use CsvParser\Parser;
use CsvParser\Middleware\FormulaInjectionMiddleware;
use CsvParser\Middleware\DatetimeMiddleware;

class MiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function testStringMiddleware()
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

    public function testStreamMiddleware()
    {
        $parser = new Parser();
        $parser->addMiddleware(new FormulaInjectionMiddleware);
        $parser->addMiddleware(new DatetimeMiddleware);

        // Create a temporary CSV file with formula injection attempts
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        $testData = <<<CSV
Name,Comment,Date
Alice,"=HYPERLINK(""http://malicious.com"",""Click me!"")","2024-01-01"
Bob,"Hello world!","2024-02-15 12:30:00"
CSV;
        file_put_contents($tempFile, $testData);

        // Test StreamReader with middleware
        $results = [];
        foreach ($parser->fromStream($tempFile) as $row) {
            $results[] = $row;
        }

        // Verify middleware was applied on read (formulas should be unescaped, dates kept as-is)
        $expected = [
            ['Name' => 'Alice', 'Comment' => '=HYPERLINK("http://malicious.com","Click me!")', 'Date' => '2024-01-01'],
            ['Name' => 'Bob', 'Comment' => 'Hello world!', 'Date' => '2024-02-15 12:30:00'],
        ];
        $this->assertSame($expected, $results);

        // Test StreamWriter with middleware
        $outputFile = tempnam(sys_get_temp_dir(), 'csv_output_');
        $resource = fopen($outputFile, 'w');

        $dataToWrite = [
            ['Name' => 'Charlie', 'Comment' => '=SUM(A1:A10)', 'Date' => '2024-03-20'],
            ['Name' => 'Diana', 'Comment' => 'Normal text', 'Date' => '2024-04-10 08:00:00'],
        ];

        $index = 0;
        $callback = function() use (&$dataToWrite, &$index) {
            if ($index < count($dataToWrite)) {
                return $dataToWrite[$index++];
            }
            return null;
        };

        $parser->toStream($resource, ['Name', 'Comment', 'Date'], $callback);
        fclose($resource);

        // Read back and verify formulas were escaped
        $output = file_get_contents($outputFile);
        $this->assertStringContainsString("'=SUM(A1:A10)", $output);
        $this->assertStringContainsString("'2024-03-20", $output);
        $this->assertStringContainsString("Normal text", $output);

        // Cleanup
        unlink($tempFile);
        unlink($outputFile);
    }
}
