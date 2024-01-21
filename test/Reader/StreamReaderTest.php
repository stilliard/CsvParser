<?php

use CsvParser\Parser;
use CsvParser\Writer\FileWriter;
use CsvParser\Reader\StreamReader;

class StreamReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testRead()
    {
        $stream = Parser::stream(__DIR__ . '/data/products.csv');

        foreach ($stream as $i => $row) {
            if ($i === 0) {
                $this->assertEquals([
                    'id' => '1',
                    'product name' => 'Hot Sauces',
                    'description' => '<p>Spicy new hot sauce for sale in this multi pack!</p>',
                    'price' => '12.99',
                ], $row);
            } elseif ($i === 1) {
                $this->assertEquals([
                    'id' => '2',
                    'product name' => 'Vegan Mayo',
                    'description' => 'New garlic infused vegan mayo, great on:
- sandwiches
- salads
- fries',
                    'price' => '3.99',
                ], $row);
            }
        }
    }
}

