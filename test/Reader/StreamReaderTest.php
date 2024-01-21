<?php

use CsvParser\Parser;
use CsvParser\Writer\FileWriter;
use CsvParser\Reader\StreamReader;

class StreamReaderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() : void
    {
        $this->parser = new Parser;
    }

    public function testRead()
    {
        $stream = StreamReader::read($this->parser, __DIR__ . '/data/products.csv');
        $this->assertEquals([
            'id' => '1',
            'product name' => 'Hot Sauces',
            'description' => '<p>Spicy new hot sauce for sale in this multi pack!</p>',
            'price' => '12.99',
        ], $stream->current());

        $stream->next();
        $this->assertEquals([
            'id' => '2',
            'product name' => 'Vegan Mayo',
            'description' => 'New garlic infused vegan mayo, great on:
- sandwiches
- salads
- fries',
            'price' => '3.99',
        ], $stream->current());
    }
}

