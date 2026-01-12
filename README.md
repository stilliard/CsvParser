# Csv Parser
Quickly take in and output csv formats.

[![Build Status](https://travis-ci.org/stilliard/CsvParser.png?branch=master)](https://travis-ci.org/stilliard/CsvParser)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/stilliard/CsvParser/badges/quality-score.png?s=3f821d3231d78e86c41c9cd9213c68f164bb53d6)](https://scrutinizer-ci.com/g/stilliard/CsvParser/)
[![Code Coverage](https://scrutinizer-ci.com/g/stilliard/CsvParser/badges/coverage.png?s=dbc9d91b767b84a1a649b5695b8a3cdce690684a)](https://scrutinizer-ci.com/g/stilliard/CsvParser/)
[![Latest Stable Version](https://poser.pugx.org/stilliard/csvparser/v/stable.png)](https://packagist.org/packages/stilliard/csvparser) [![Total Downloads](https://poser.pugx.org/stilliard/csvparser/downloads.png)](https://packagist.org/packages/stilliard/csvparser) [![Latest Unstable Version](https://poser.pugx.org/stilliard/csvparser/v/unstable.png)](https://packagist.org/packages/stilliard/csvparser) [![License](https://poser.pugx.org/stilliard/csvparser/license.png)](https://packagist.org/packages/stilliard/csvparser)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fstilliard%2FCsvParser.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Fstilliard%2FCsvParser?ref=badge_shield)

## Install
```bash
composer require stilliard/csvparser 1.4.4
```

## Example usage:
```php
use CsvParser\Parser;
//
// Simple array to string usage
//
$array = [['id'=>1, 'name'=>'Bob'],['id'=>2, 'name'=>'Bill']];
$parser = new Parser();
$csv = $parser->fromArray($array);
var_dump($parser->toString($csv));
```

## Example stream reading (better memory optimisations)
```php
// stream reading from a CSV file
foreach (Parser::stream(__DIR__ . '/your/path/input.csv') as $row) {
    var_dump($row);
}
// write file
Parser::write($data, __DIR__ . '/your/path/output.csv');
```


```php
//
// Full power examples:
//

// setup initial parser
$parser = new \CsvParser\Parser(',', '"', "\n");

// change settings after init
// set column delimiter
$parser->fieldDelimiter = ';';
// set text enclosure
$parser->fieldEnclosure = "'";
// set line delimiter
$parser->lineDelimiter = "\n";

// Input (returns instance of \CsvParser\Csv)
$csv = $parser->fromArray([['id'=>1, 'name'=>'Bob'],['id'=>2, 'name'=>'Bill']]);
$csv = $parser->fromString("id,name\n1,Bob\n2,Bill");
$csv = $parser->fromFile('demo.csv');

// get row count
var_dump($csv->getRowCount());

// get the first row as array from the csv
var_dump($csv->first());

// get the column headings / keys
var_dump($csv->getKeys());

// want to force a column sort / index?
$csv->reKey(['id', 'name', 'email']);

// append/prepend rows
$csv->appendRow(['id'=>3, 'name'=>'Ben']);
$csv->prependRow(['id'=>4, 'name'=>'Barry']);

// map function over column
$csv->mapColumn('name', 'trim');
$csv->mapColumn('name', function ($name) {
    return trim($name);
});

// map function over rows
$csv->mapRows(function ($row) {
    $row['codename'] = base64_encode($row['id']);
    return $row;
});

// add a column
$csv->addColumn('codename', 'default value');

// remove a column
$csv->removeColumn('codename');

// filter down rows
$csv->filterRows(function ($row) {
    return $row['id'] != '#'; // remove rows where the id column just has a hash inside
});

// remove row by index
$csv->removeRowByIndex(4);
// or remove row(s) by column value, such as id 22
$csv->removeRow('id', 22);
// or remove row(s) by multiple creiteria, such as when id 22 AND when name is 'some name'
$csv->removeRows(['id'=>22, 'name'=>'some name']);

// Column reordering
$csv->reorderColumn('colname', 0); // move to position 0 (the start)
// or multiple
$csv->reorderColumns(['colname1'=>0, 'colname2'=>4]);

// Row reordering
// to move the row with id of 22 to the start
$csv->reorderRow('id', 22, 0);
// or move id 22 to the start, and id 5 after it
$csv->reorderRows('id', [22 => 0, 5 => 1]);

// Sort rows by a column
$csv->reorderRowsByColumn('id', 'desc');
// or even multiples:
$csv->reorderRowsByColumns(['name', 'id' => 'desc']);

// Output
var_dump($parser->toArray($csv));
var_dump($parser->toString($csv));
var_dump($parser->toFile($csv, 'demo.csv')); // file was created?

// Need to chunk into multiple chunks/files?
$chunks = $parser->toChunks($csv, 1000);
foreach ($chunks as $i => $chunk) {
    $parser->toFile($chunk, "output-{$i}.csv");
}

// Remove duplicates
$csv->removeDuplicates('email');

// Remove blanks
$csv->removeBlanks('email');

```

## Writing CSV as a Stream

The `writeStream` method allows you to write CSV data as a stream. This can be useful for writing large datasets efficiently.

### Example: Writing to a File

```php
use CsvParser\Parser;

$file = fopen('output.csv', 'w');

$callback = function () {
    static $data = [
        ['name' => 'John', 'age' => 30],
        ['name' => 'Jane', 'age' => 25],
        ['name' => 'Doe', 'age' => 40],
    ];
    return array_shift($data);
};

Parser::writeStream($file, ['name', 'age'], $callback);

fclose($file);
```

### Example: Writing to the Screen

```php
use CsvParser\Parser;

$resource = fopen('php://output', 'w');

$callback = function () {
    $data = [
        ['name' => 'John', 'age' => 30],
        ['name' => 'Jane', 'age' => 25],
        ['name' => 'Doe', 'age' => 40],
    ];
    foreach ($data as $row) {
        yield $row;
    }
};

Parser::writeStream($resource, ['name', 'age'], $callback);

fclose($resource);
```

### Example: Writing from a PDO Fetch

```php
use CsvParser\Parser;
use PDO;

$pdo = new PDO('mysql:host=localhost;dbname=testdb', 'username', 'password');
$stmt = $pdo->query('SELECT name, age FROM users');

$file = fopen('output.csv', 'w');

Parser::writeStream($file, ['name', 'age'], fn() => $stmt->fetch(PDO::FETCH_NUM));

fclose($file);
```

In this example, the `callback` function uses a PDO statement to fetch rows from a database. The `writeStream` method will continue to call the `callback` until it returns `false`.

## Test
To run the tests, you can use PHPUnit. Make sure you have PHPUnit installed and then run:

```bash
phpunit .
```

## Middleware

You can use middleware to modify data as it is read or written.

### Available Middleware

- **FormulaInjectionMiddleware**: Protects against formula injection (CSV Injection) by escaping characters that could be interpreted as formulas.
- **DatetimeMiddleware**: Detects dates and adds an escape character so that spreadsheet apps don't auto-convert formats.
- **EncodingCheckMiddleware**: Validates input encoding (default UTF-8) and can warn, throw exception, or attempt to fix invalid encoding. It can also fix "mojibake" (double-encoded text).

### Usage

```php
use CsvParser\Parser;
use CsvParser\Middleware\FormulaInjectionMiddleware;
use CsvParser\Middleware\DatetimeMiddleware;
use CsvParser\Middleware\TextFieldMiddleware;
use CsvParser\Middleware\EncodingCheckMiddleware;

$parser = new Parser();

// Protect against CSV formula injection
$parser->addMiddleware(new FormulaInjectionMiddleware());

// Escape date/datetime fields to prevent auto-conversion
$parser->addMiddleware(new DatetimeMiddleware());

// Escape specific text fields
$parser->addMiddleware(new TextFieldMiddleware([
    'fields' => ['long_id', 'phone_number'],
]);

// Encoding check options:
// action: 'warn' (default), 'throw', or 'fix'
// fixMojibake: true/false (default false) - attempts to fix double-encoded text (e.g. Ã© -> é)
$parser->addMiddleware(new EncodingCheckMiddleware([
    'action' => 'warn',
    'fixMojibake' => true,
]));

// ... use parser as normal
```

See the [`MiddlewareTest.php`](test/MiddlewareTest.php) file for more usage examples.


## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fstilliard%2FCsvParser.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Fstilliard%2FCsvParser?ref=badge_large)
