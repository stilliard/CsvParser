<?php

require_once dirname(__FILE__) . '/../vendor/autoload.php';

class CsvTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->string = "id,name\n1,Bob\n2,Bill";
        $this->parser = new \CsvParser\Parser();
        $this->csv = $this->parser->fromString($this->string);
    }

    public function testAppendRow()
    {
        $actual = $this->parser->toArray($this->csv);
        $expected = [['id'=>1, 'name'=>'Bob'],['id'=>2, 'name'=>'Bill']];
        $this->assertEquals($expected, $actual);

        $this->csv->appendRow(['id'=>3, 'name'=>'Ben']);
        $actual = $this->parser->toArray($this->csv);
        $expected = [['id'=>3, 'name'=>'Ben'],['id'=>1, 'name'=>'Bob'],['id'=>2, 'name'=>'Bill']];
        $this->assertEquals($expected, $actual);
    }

    public function testPrependRow()
    {
        $this->string = "id,name\n1,Bob\n2,Bill";
        $this->parser = new \CsvParser\Parser();
        $this->csv = $this->parser->fromString($this->string);

        $this->csv->prependRow(['id'=>3, 'name'=>'Ben']);
        $actual = $this->parser->toArray($this->csv);
        $expected = [['id'=>1, 'name'=>'Bob'],['id'=>2, 'name'=>'Bill'],['id'=>3, 'name'=>'Ben']];
        $this->assertEquals($expected, $actual);
    }

    public function testGetRowCount()
    {
        $this->string = "id,name\n1,Bob\n2,Bill";
        $this->parser = new \CsvParser\Parser();
        $this->csv = $this->parser->fromString($this->string);

        $this->assertEquals(2, $this->csv->getRowCount());

        $this->csv->appendRow(['id'=>3, 'name'=>'Ben']);
        $this->assertEquals(3, $this->csv->getRowCount());

        $this->csv->prependRow(['id'=>77, 'name'=>'Benji']);
        $this->assertEquals(4, $this->csv->getRowCount());
    }

    public function testColumnExists()
    {
        $this->string = "id,name\n1,Bob\n2,Bill";
        $this->parser = new \CsvParser\Parser();
        $this->csv = $this->parser->fromString($this->string);

        $this->assertTrue($this->csv->columnExists('name'));
        $this->assertFalse($this->csv->columnExists('somethingelse'));
    }

    public function testMapColumn()
    {
        $this->csv->mapColumn('name', function ($val) {
            return 'Sir ' . $val;
        });
        $actual = $this->parser->toArray($this->csv);
        $expected = [['id'=>1, 'name'=>'Sir Bob'],['id'=>2, 'name'=>'Sir Bill']];
        $this->assertEquals($expected, $actual);
    }

    public function testMapRows()
    {
        $this->csv->mapRows(function ($row) {
            $row['codename'] = base64_encode($row['id']);
            return $row;
        });
        $actual = $this->parser->toArray($this->csv);
        $expected = [['id'=>1, 'name'=>'Bob','codename'=>'MQ=='],['id'=>2, 'name'=>'Bill','codename'=>'Mg==']];
        $this->assertEquals($expected, $actual);
    }

    public function testFilterRows()
    {
        $this->csv->filterRows(function ($row) {
            return $row['id'] != 2;
        });
        $actual = $this->parser->toArray($this->csv);
        $expected = [['id'=>1, 'name'=>'Bob']];
        $this->assertEquals($expected, $actual);
    }
}
