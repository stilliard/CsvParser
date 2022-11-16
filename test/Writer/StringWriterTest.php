<?php

use CsvParser\Parser;
use CsvParser\Writer\StringWriter;

class StringWriterTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() : void
    {
        $this->parser = new Parser(',', '');
    }

    public function testWriteAssoc()
    {
        $input = array(array( 'a' => 1, 'b' => 2, 'c' => 44 ));
        $expected = "a,b,c\n1,2,44";
        $actual = StringWriter::write($this->parser, $this->parser->fromArray($input));
        $this->assertSame($expected, $actual);
    }

    public function testWriteNumeric()
    {
        $input = array(array( 1, 2, 44 ));
        // numeric arrays should not have a header
        $expected = "1,2,44";
        $actual = StringWriter::write($this->parser, $this->parser->fromArray($input));
        $this->assertSame($expected, $actual);

        $input = array(array( '1' => 1, '2' => 2, '3' => 44 ));
        // however if they keys are strings, even if numbers, they should have a header
        $expected = "1,2,3\n1,2,44";
        $actual = StringWriter::write($this->parser, $this->parser->fromArray($input));
        $this->assertSame($expected, $actual);
    }
}

