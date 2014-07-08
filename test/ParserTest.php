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

    // example from http://en.wikipedia.org/wiki/Comma-separated_values
    public function testFromStringToArraySubQuotes()
    {
        $string = 'Year,Make,Model,Description,Price
1997,Ford,E350,"ac, abs, moon",3000.00
1999,Chevy,"Venture ""Extended Edition""","",4900.00
1999,Chevy,"Venture ""Extended Edition, Very Large""",,5000.00
1996,Jeep,Grand Cherokee,"MUST SELL!
air, moon roof, loaded",4799.00';
        $parser = new \CsvParser\Parser();
        $csv = $parser->fromString($string);
        $actual = $parser->toArray($csv);
        
        $expected = array(
            array(
                'Year' => '1997',
                'Make' => 'Ford',
                'Model' => 'E350',
                'Description' => 'ac, abs, moon',
                'Price' => '3000.00',
            ),
            array(
                'Year' => '1999',
                'Make' => 'Chevy',
                'Model' => 'Venture "Extended Edition"',
                'Description' => '',
                'Price' => '4900.00',
            ),
            array(
                'Year' => '1999',
                'Make' => 'Chevy',
                'Model' => 'Venture "Extended Edition, Very Large"',
                'Description' => '',
                'Price' => '5000.00',
            ),
            array(
                'Year' => '1996',
                'Make' => 'Jeep',
                'Model' => 'Grand Cherokee',
                'Description' => 'MUST SELL!
air, moon roof, loaded',
                'Price' => '4799.00',
            ),
        );
        $this->assertEquals($expected, $actual);
    }
}
