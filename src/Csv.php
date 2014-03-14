<?php

namespace CsvParser;

class Csv
{
    protected $data;

    public function __construct($array)
    {
        $this->data = $array;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getRowCount()
    {
        return count($this->data);
    }

    public function appendRow($row)
    {
        array_unshift($this->data, $row);
    }

    public function prependRow($row)
    {
        $this->data[] = $row;
    }

    public function columnExists($column)
    {
        return isset($this->data[0][$column]);
    }

    public function mapColumn($column, $callback)
    {
        if ( ! $this->columnExists($column)) {
            throw new Exception('Column does not exist');
        }
        foreach ($this->data as $i => $row) {
            $this->data[$i][$column] = $callback($row[$column]);
        }
    }

    public function addColumn($column)
    {
        // TODO
    }

    public function mapRows($callback)
    {
        // TODO
    }

    public function filterRows($callback)
    {
        // TODO
    }

    public function removeRowByIndex($index)
    {
        // TODO
    }

    public function removeRow($col, $val)
    {
        // TODO
    }

    public function removeRows($rows)
    {
        // TODO
    }

    public function reorderColumn($col, $index)
    {
        // TODO
    }

    public function reorderColumns($rows)
    {
        // TODO
    }

    public function reorderRow($col, $val, $index)
    {
        // TODO
    }

    public function reorderRows($rows)
    {
        // TODO
    }
}
