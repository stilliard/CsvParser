<?php

use CsvParser\Encoding\StrictUtf8Converter;

class StrictUtf8ConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testValidUtf8PassesThrough()
    {
        $converter = new StrictUtf8Converter();
        $input = "François Müller lives in München and works at Café Parisien";
        $output = $converter->convert($input);

        $this->assertEquals($input, $output);
    }

    public function testUtf8WithBomIsStripped()
    {
        $converter = new StrictUtf8Converter();
        $input = "\xEF\xBB\xBF" . "François Müller";
        $expected = "François Müller";

        $output = $converter->convert($input);

        $this->assertEquals($expected, $output);
    }

    public function testInvalidUtf8ThrowsException()
    {
        $converter = new StrictUtf8Converter();

        // Create a string with invalid UTF-8 (ISO-8859-1 encoded)
        $input = "Fran\xE7ois M\xFCller"; // ç and ü in ISO-8859-1

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File is not encoded in UTF-8');

        $converter->convert($input);
    }

    public function testDoubleEncodedContentThrowsException()
    {
        $converter = new StrictUtf8Converter();

        // Double-encoded UTF-8 (mojibake) - appears valid UTF-8 but contains mojibake patterns
        // This simulates what happens when ISO-8859-1 is incorrectly treated as UTF-8
        $input = "François becomes FranÃ§ois, München becomes MÃ¼nchen, Café becomes CafÃ©";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('double-encoded');

        $converter->convert($input);
    }

    public function testLegitimateUtf8WithFewMojibakePatternsDoesNotThrow()
    {
        $converter = new StrictUtf8Converter();

        // Valid UTF-8 that happens to contain mojibake-like patterns in documentation
        // Only 2 patterns - below the threshold of 3
        $input = "Example mojibake patterns: Ã¤ and Ã¶ are often seen";

        // Should NOT throw because pattern count is below threshold
        $output = $converter->convert($input);

        $this->assertEquals($input, $output);
    }

    public function testUtf16ThrowsException()
    {
        $converter = new StrictUtf8Converter();

        // UTF-16LE BOM followed by UTF-16 encoded content
        $input = "\xFF\xFE" . "F\x00r\x00a\x00n\x00";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File is not encoded in UTF-8');

        $converter->convert($input);
    }

    public function testWindows1252ThrowsException()
    {
        $converter = new StrictUtf8Converter();

        // Windows-1252 encoded string with smart quotes and em dash
        $input = "The \x93quote\x94 and \x97 dash"; // Smart quotes and em dash in Windows-1252

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File is not encoded in UTF-8');

        $converter->convert($input);
    }

    public function testEmptyStringPassesThrough()
    {
        $converter = new StrictUtf8Converter();
        $input = "";

        $output = $converter->convert($input);

        $this->assertEquals("", $output);
    }

    public function testExceptionMessageProvidesHelpfulGuidance()
    {
        $converter = new StrictUtf8Converter();
        $input = "Fran\xE7ois"; // Invalid UTF-8

        try {
            $converter->convert($input);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Verify the exception message contains helpful guidance
            $this->assertStringContainsString('UTF-8', $e->getMessage());
            $this->assertStringContainsString('convert', $e->getMessage());
        }
    }
}
