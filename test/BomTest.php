<?php

use CsvParser\Parser;

class BomTest extends \PHPUnit_Framework_TestCase
{
    // Quoted headings are what our own writers produce, and what broke the stream reader
    private string $csv = "\"ID\",\"Name\"\n\"1\",\"Industriegelände 1\"\n";

    private array $expected = [
        ['ID' => '1', 'Name' => 'Industriegelände 1'],
    ];

    private function tmpFile(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($file, $contents);
        return $file;
    }

    public function testStreamReaderSkipsBomBeforeQuotedHeading()
    {
        $file = $this->tmpFile(Parser::BOM . $this->csv);
        $this->assertEquals($this->expected, iterator_to_array(Parser::stream($file)));
    }

    public function testStreamReaderWithoutBom()
    {
        $file = $this->tmpFile($this->csv);
        $this->assertEquals($this->expected, iterator_to_array(Parser::stream($file)));
    }

    public function testStreamReaderWithBomAndUnquotedHeading()
    {
        $file = $this->tmpFile(Parser::BOM . "ID,Name\n1,Bob\n");
        $this->assertEquals([['ID' => '1', 'Name' => 'Bob']], iterator_to_array(Parser::stream($file)));
    }

    public function testFromStringStripsBom()
    {
        $parser = new Parser();
        $csv = $parser->fromString(Parser::BOM . $this->csv);
        $this->assertEquals($this->expected, $parser->toArray($csv));
    }

    public function testFromFileStripsBom()
    {
        $parser = new Parser();
        $csv = $parser->fromFile($this->tmpFile(Parser::BOM . $this->csv));
        $this->assertEquals($this->expected, $parser->toArray($csv));
    }

    public function testStringWriterNoBomByDefault()
    {
        $parser = new Parser();
        $out = $parser->toString($parser->fromArray([['ID' => 1]]));
        $this->assertEquals("\"ID\"\n\"1\"", $out);
    }

    public function testStringWriterWithBom()
    {
        $parser = new Parser();
        $parser->bom = true;
        $out = $parser->toString($parser->fromArray([['ID' => 1]]));
        $this->assertEquals(Parser::BOM . "\"ID\"\n\"1\"", $out);
    }

    public function testWriteWithBomOption()
    {
        $file = tempnam(sys_get_temp_dir(), 'csv');
        Parser::write([['ID' => 1]], $file, ['bom' => true]);
        $this->assertStringStartsWith(Parser::BOM . '"ID"', file_get_contents($file));
    }

    public function testStreamWriterWithBomOption()
    {
        $handle = fopen('php://temp', 'w+');
        Parser::writeStream($handle, ['ID'], fn () => yield [1], ['bom' => true]);
        rewind($handle);
        $this->assertEquals(Parser::BOM . "\"ID\"\n\"1\"\n", stream_get_contents($handle));
    }

    public function testStreamWriterNoBomByDefault()
    {
        $handle = fopen('php://temp', 'w+');
        Parser::writeStream($handle, ['ID'], fn () => yield [1]);
        rewind($handle);
        $this->assertEquals("\"ID\"\n\"1\"\n", stream_get_contents($handle));
    }

    public function testRoundTripWithBom()
    {
        $file = tempnam(sys_get_temp_dir(), 'csv');
        Parser::write($this->expected, $file, ['bom' => true]);
        $this->assertEquals($this->expected, iterator_to_array(Parser::stream($file)));
    }
}
