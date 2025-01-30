<?php

use CsvParser\Parser;
use CsvParser\Writer\StreamWriter;

class StreamWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testWriteStreamToFile()
    {
        $parser = new Parser();
        $file = fopen('php://temp', 'w+');
        $keys = ['name', 'age'];

        $callback = function() {
            static $data = [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Doe', 'age' => 40],
            ];
            return array_shift($data);
        };

        StreamWriter::write($parser, $file, $keys, $callback);

        rewind($file);
        $content = stream_get_contents($file);
        fclose($file);

        $expected = "\"name\",\"age\"\n\"John\",\"30\"\n\"Jane\",\"25\"\n\"Doe\",\"40\"\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteStreamToOutput()
    {
        $parser = new Parser();
        $file = fopen('php://output', 'w');
        $keys = ['name', 'age'];

        $callback = function() {
            static $data = [
                ['name' => 'John', 'age' => 30],
                ['name' => 'Jane', 'age' => 25],
                ['name' => 'Doe', 'age' => 40],
            ];
            return array_shift($data);
        };

        ob_start();
        StreamWriter::write($parser, $file, $keys, $callback);
        $content = ob_get_clean();

        $expected = "\"name\",\"age\"\n\"John\",\"30\"\n\"Jane\",\"25\"\n\"Doe\",\"40\"\n";
        $this->assertEquals($expected, $content);
    }
}
