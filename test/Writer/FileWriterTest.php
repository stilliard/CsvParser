<?php

use CsvParser\Parser;
use CsvParser\Writer\FileWriter;

class FileWriterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->parser = new Parser(',', '');
    }

    public function testWrite()
    {
        $input = [[ 'a' => 1, 'b' => 2, 'c' => 44 ]];
        $tmpDir = dirname(__FILE__) . '/../../tmp/';
        $filename = $tmpDir . 'csv_parser_file_test.csv';
        $result = FileWriter::write(
            $this->parser,
            $this->parser->fromArray($input),
            $filename
        );
        $this->assertTrue( !! $result);
        $this->assertFileExists($filename);
        $fileContents = file_get_contents($filename);
        $expected = "a,b,c\n1,2,44";
        $this->assertSame($expected, $fileContents);
        // cleanup
        unlink($filename);
    }

    /**
     * @expectedException CsvParser\Exception
     */
    public function testWriteFailShowsExceptionWhenNoFileNameGiven()
    {
        $input = [[ 'a' => 1, 'b' => 2, 'c' => 44 ]];
        $actual = FileWriter::write($this->parser, $this->parser->fromArray($input));
    }
}

