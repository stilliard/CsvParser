<?php

use CsvParser\Parser;
use CsvParser\Middleware\FormulaInjectionMiddleware;
use CsvParser\Middleware\DatetimeMiddleware;
use CsvParser\Middleware\EncodingCheckMiddleware;
use CsvParser\Middleware\TextFieldMiddleware;

class MiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function testStringMiddleware()
    {
        $parser = new Parser();
        $parser->addMiddleware(new FormulaInjectionMiddleware);
        $parser->addMiddleware(new DatetimeMiddleware);

        $orig = [
            [
                'Name' => 'Alice',
                'Comment' => '=HYPERLINK("http://malicious.com","Click me!")',
                'Date' => '2024-01-01',
                'Num' => 123,
            ],
            [
                'Name' => 'Bob',
                'Comment' => 'Hello ol\' world!',
                'Date' => '2024-02-15 12:30:00',
                'Num' => '	+456',
            ],
        ];
        $csv = $parser->fromArray($orig);

        // check that the formula injection was escaped on write
        $safe = <<<CSV
        "Name","Comment","Date","Num"
        "Alice","'=HYPERLINK(""http://malicious.com"",""Click me!"")","'2024-01-01","123"
        "Bob","Hello ol' world!","'2024-02-15 12:30:00","'	+456"
        CSV;
        $this->assertSame($safe, $parser->toString($csv));

        // check that the formula injection was unescaped on read
        $csv2 = $parser->fromString($safe);
        $orig[0]['Num'] = (string) $orig[0]['Num']; // when we parse back from string, numbers become strings, this is intended
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

    public function testEncodingCheckMiddlewareWarns()
    {
        $csvContent = "col1,col2\nValid,\x80Invalid";
        $parser = new Parser();
        $parser->addMiddleware(new EncodingCheckMiddleware(['action' => 'warn']));

        $warnings = [];
        set_error_handler(function ($errno, $errstr) use (&$warnings) {
            $warnings[] = $errstr;
            return true;
        }, E_USER_WARNING);

        try {
            $parser->fromString($csvContent);
        } finally {
            restore_error_handler();
        }

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString("Invalid encoding detected", $warnings[0]);
    }

    public function testEncodingCheckMiddlewareThrows()
    {
        $csvContent = "col1,col2\nValid,\x80Invalid";
        $parser = new Parser();
        $parser->addMiddleware(new EncodingCheckMiddleware(['action' => 'throw']));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Invalid encoding detected");

        $parser->fromString($csvContent);
    }

    public function testEncodingCheckMiddlewareFixes()
    {
        $csvContent = "col1,col2\nValid,\xE9";

        // 1. Verify broken without middleware
        $parser = new Parser();
        $csv = $parser->fromString($csvContent);
        $rows = $csv->getData();
        $this->assertFalse(mb_check_encoding($rows[0]['col2'], 'UTF-8'));
        $this->assertNotEquals("é", $rows[0]['col2']); // it'd become � instead without the middleware fix

        // 2. Verify fixed with middleware
        $parser = new Parser();
        $parser->addMiddleware(new EncodingCheckMiddleware(['action' => 'fix', 'fallbackEncoding' => 'ISO-8859-1']));

        $csv = $parser->fromString($csvContent);
        $rows = $csv->getData();

        $this->assertEquals("é", $rows[0]['col2']); // Should be UTF-8 'é'
        $this->assertEquals("\xC3\xA9", $rows[0]['col2']); // Check correct byte sequence (same as above but for additional clarity)
        $this->assertEquals("\u{00E9}", $rows[0]['col2']); // Check correct character as unicode (as above)
        $this->assertTrue(mb_check_encoding($rows[0]['col2'], 'UTF-8'));
    }

    public function testEncodingCheckMiddlewareValidPasses()
    {
        $csvContent = "col1\nValid UTF-8: é";
        $parser = new Parser();
        $parser->addMiddleware(new EncodingCheckMiddleware(['action' => 'throw']));

        $csv = $parser->fromString($csvContent);
        $rows = $csv->getData();

        $this->assertEquals("Valid UTF-8: é", $rows[0]['col1']);
    }

    public function testEncodingCheckMiddlewareFixesMojibake()
    {
        // "é" (UTF-8: C3 A9) interpreted as Windows-1252 becomes "Ã©"
        // "Ã©" in UTF-8 is C3 83 C2 A9
        $mojibake = "\xC3\x83\xC2\xA9";
        $csvContent = "col1\n{$mojibake}";

        // 1. Verify it stays as mojibake without the fix option
        $parser = new Parser();
        $parser->addMiddleware(new EncodingCheckMiddleware(['action' => 'warn'])); // default doesn't fix mojibake

        // Suppress warning for this test part if any (though mojibake is valid utf8 so shouldn't warn)
        $csv = $parser->fromString($csvContent);
        $rows = $csv->getData();
        $this->assertEquals($mojibake, $rows[0]['col1']);

        // 2. Verify it gets fixed with fixMojibake = true
        $parser = new Parser();
        $parser->addMiddleware(new EncodingCheckMiddleware([
            'action' => 'warn',
            'fixMojibake' => true,
            'fallbackEncoding' => 'Windows-1252'
        ]));

        $csv = $parser->fromString($csvContent);
        $rows = $csv->getData();

        $this->assertEquals("é", $rows[0]['col1']);
        $this->assertEquals("\xC3\xA9", $rows[0]['col1']); // Check correct byte sequence (same as above but for additional clarity)
        $this->assertEquals("\u{00E9}", $rows[0]['col1']); // Check correct character as unicode (as above)
        $this->assertTrue(mb_check_encoding($rows[0]['col1'], 'UTF-8'));
    }

    public function testTextFieldMiddleware()
    {
        $parser = new Parser();
        $parser->addMiddleware(new TextFieldMiddleware([
            'fields' => ['long_id', 'phone_number'],
        ]));

        $orig = [
            [
                'name' => 'Alice',
                'long_id' => 1234567890123456789,
                'phone_number' => 1234567890,
                'email' => 'alice@email.test',
            ],
            [
                'name' => 'Bob',
                'long_id' => '09876543210987654321',
                'phone_number' => '',
                'email' => 'bob@email.test',
            ],
        ];
        $csv = $parser->fromArray($orig);

        $safe = <<<CSV
        "name","long_id","phone_number","email"
        "Alice","'1234567890123456789","'1234567890","alice@email.test"
        "Bob","'09876543210987654321","","bob@email.test"
        CSV;
        $this->assertSame($safe, $parser->toString($csv));

        // and back again
        $csv2 = $parser->fromString($safe);
        $orig[0]['long_id'] = (string) $orig[0]['long_id']; // fix to string as when we read from CSV all values become strings
        $orig[0]['phone_number'] = (string) $orig[0]['phone_number'];
        $this->assertSame($orig, $csv2->getData());

        // and now with the stream reader/writer
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        $resource = fopen($tempFile, 'w');
        $index = 0;
        $parser->toStream($resource, ['name', 'long_id', 'phone_number', 'email'], function() use ($orig, &$index) {
            return $index < count($orig) ? $orig[$index++] : null;
        });
        fclose($resource);
        $this->assertSame($safe . "\n", file_get_contents($tempFile));

        $results = [];
        foreach ($parser->fromStream($tempFile) as $row) {
            $results[] = $row;
        }
        $this->assertSame($orig, $results);
    }
}
