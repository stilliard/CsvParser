<?php

require_once dirname(__FILE__) . '/../vendor/autoload.php';

class ParserTest extends PHPUnit_Framework_TestCase
{
    public function testFromStringToArraySimple()
    {
        $string = "id,name\n1,Bob\n2,Bill";
        $parser = new \CsvParser\Parser();
        $csv = $parser->fromString($string);
        $actual = $parser->toArray($csv);
        
        $expected = array(array('id'=>1, 'name'=>'Bob'),array('id'=>2, 'name'=>'Bill'));
        $this->assertEquals($expected, $actual);
    }
    
    public function testFromArrayToStringSimple()
    {
        $array = array(array('id'=>1, 'name'=>'Bob'),array('id'=>2, 'name'=>'Bill'));
        $parser = new \CsvParser\Parser();
        $csv = $parser->fromArray($array);
        $actual = $parser->toString($csv);
        
        $expected = '"id","name"
"1","Bob"
"2","Bill"';
        $this->assertEquals($expected, $actual);
    }
}
