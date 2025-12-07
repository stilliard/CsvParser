<?php

use CsvParser\Encoding\BasicEncodingConverter;
use CsvParser\Parser;
use CsvParser\Reader\FileReader;

class FileReaderEncodingTest extends \PHPUnit_Framework_TestCase
{
    public function setUp(): void
    {
        FileReader::setDefaultOptions([
            'encodingConverter' => new BasicEncodingConverter(),
        ]);
    }

    public function testGermanBusinesses()
    {
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/german_business.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(3, count($rows));
        $this->assertEquals('KFZ Meisterbetrieb Hönsch e.K.', $rows[0]['Firma']);
        $this->assertEquals('Römerweg 8', $rows[1]['Straße']);
        $this->assertEquals('München', $rows[2]['Stadt']);
        $this->assertEquals('0211-123456', $rows[0]['Telefon']);
        $this->assertEquals('service@müller-brot.de', $rows[2]['Email']);
    }

    public function testIso88591Encoding()
    {
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/iso88591.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(3, count($rows));
        $this->assertEquals('Müller', $rows[0]['Name']);
        $this->assertEquals('Café München', $rows[0]['Company']);
        $this->assertEquals('Düsseldorf', $rows[0]['City']);
        $this->assertEquals('Höör AB', $rows[1]['Company']);
        $this->assertEquals('Göteborg', $rows[1]['City']);
        $this->assertEquals('Schöne Grüße GmbH', $rows[2]['Company']);
    }

    public function testIso885915Encoding()
    {
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/iso885915.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(2, count($rows));
        $this->assertEquals('Product A', $rows[0]['Name']);
        $this->assertStringContainsString('€25.50', $rows[0]['Price']);
        $this->assertEquals('Européen', $rows[0]['Notes']);
        $this->assertStringContainsString('€30.00', $rows[1]['Price']);
        $this->assertEquals('Français', $rows[1]['Notes']);
    }

    public function testWindows1252Encoding()
    {
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/windows1252.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(2, count($rows));
        $this->assertEquals('Smith', $rows[0]['Name']);
        $this->assertStringContainsString('–dash–', $rows[0]['Notes']);
        $this->assertEquals('García', $rows[1]['Name']);
        $this->assertEquals('López & Hernández', $rows[1]['Company']);
        $this->assertStringContainsString('™', $rows[1]['Notes']); // trademark
        $this->assertStringContainsString('©', $rows[1]['Notes']); // copyright
    }

    public function testWindows1252SpecialCharacters()
    {
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/win1252_special.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(4, count($rows));
        $this->assertStringContainsString('…', $rows[0]['Notes']); // ellipsis
        $this->assertStringContainsString('•', $rows[0]['Notes']); // bullet
        $this->assertStringContainsString('–', $rows[1]['Notes']); // en dash
        $this->assertStringContainsString('—', $rows[1]['Notes']); // em dash
        $this->assertStringContainsString("\u{2018}", $rows[2]['Notes']); // left single quote '
        $this->assertStringContainsString("\u{201C}", $rows[2]['Notes']); // left double quote "
        $this->assertStringContainsString('€', $rows[3]['Notes']); // Euro symbol
        $this->assertStringContainsString('™', $rows[3]['Notes']); // trademark
    }

    public function testHeavyAccents()
    {
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/heavy_accents.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(4, count($rows));
        $this->assertEquals('Édouard', $rows[0]['Name']);
        $this->assertEquals('Montréal', $rows[0]['City']);
        $this->assertEquals('École française', $rows[0]['Description']);
        $this->assertEquals('Ángela', $rows[1]['Name']);
        $this->assertEquals('Córdoba', $rows[1]['City']);
        $this->assertEquals('Niño pequeño', $rows[1]['Description']);
        $this->assertEquals('Øystein', $rows[2]['Name']);
        $this->assertEquals('Tromsø', $rows[2]['City']);
        $this->assertEquals('Sébastien', $rows[3]['Name']);
        $this->assertEquals('Crème brûlée', $rows[3]['Description']);
    }

    public function testProperUtf8()
    {
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/proper_utf8.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(3, count($rows));
        $this->assertEquals('François', $rows[0]['Name']);
        $this->assertEquals('Café München', $rows[0]['Company']);
        $this->assertEquals('São Paulo', $rows[0]['City']);
        $this->assertStringContainsString('áéíóú', $rows[0]['Notes']);
        $this->assertEquals('José', $rows[1]['Name']);
        $this->assertEquals('Niño & Niña Corp', $rows[1]['Company']);
        $this->assertStringContainsString('áéíóúñ', $rows[1]['Notes']);
        $this->assertEquals('Björk', $rows[2]['Name']);
        $this->assertStringContainsString('åäöÅÄÖ', $rows[2]['Notes']);
    }

    public function testUtf16LEEncoding()
    {
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/utf16le.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(2, count($rows));
        $this->assertEquals('François', $rows[0]['Name']);
        $this->assertEquals('Café München', $rows[0]['Company']);
        $this->assertEquals('São Paulo', $rows[0]['City']);
        $this->assertStringContainsString('€', $rows[0]['Price']);
        $this->assertEquals('José', $rows[1]['Name']);
        $this->assertEquals('Niño Corp', $rows[1]['Company']);
        $this->assertEquals('España', $rows[1]['City']);
        $this->assertStringContainsString('£', $rows[1]['Price']);
    }

    public function testUtf8WithBom()
    {
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/utf8_bom.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(2, count($rows));
        $this->assertEquals('François', $rows[0]['Name']);
        $this->assertEquals('Café München', $rows[0]['Company']);
        $this->assertEquals('São Paulo', $rows[0]['City']);
        $this->assertEquals('José', $rows[1]['Name']);
        $this->assertEquals('Niño Corp', $rows[1]['Company']);
        $this->assertEquals('España', $rows[1]['City']);
    }

    public function testDoubleEncodedFixing()
    {
        // This test verifies that we correctly fix actual double-encoded content
        // (e.g., Windows-1252 text that was incorrectly interpreted as UTF-8 and re-encoded)
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/double_encoded.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(3, count($rows));

        // Verify the double-encoding was fixed
        // Row 0: French and German characters
        $this->assertEquals('François Müller', $rows[0]['Name']);
        $this->assertEquals('München', $rows[0]['City']);
        $this->assertEquals('Café Parisien', $rows[0]['Company']);
        $this->assertStringContainsString('Schöne Grüße', $rows[0]['Notes']);
        $this->assertStringContainsString('José', $rows[0]['Notes']);

        // Row 1: Spanish characters
        $this->assertEquals('García López', $rows[1]['Name']);
        $this->assertEquals('Córdoba', $rows[1]['City']);
        $this->assertEquals('Niño & Associés', $rows[1]['Company']);
        $this->assertStringContainsString('ñ', $rows[1]['Notes']);
        $this->assertStringContainsString('á é í ó ú', $rows[1]['Notes']);

        // Row 2: Scandinavian and German characters
        $this->assertEquals('Åsa Öberg', $rows[2]['Name']);
        $this->assertEquals('Zürich', $rows[2]['City']);
        $this->assertEquals('Européen Ltd', $rows[2]['Company']);
        $this->assertStringContainsString('ä ö ü ß', $rows[2]['Notes']);
    }

    public function testLegitimateUtf8WithFewMojibakePatterns()
    {
        // This test ensures that valid UTF-8 content containing FEWER than 3 mojibake-like patterns
        // is NOT "fixed" because it's likely intentional content (e.g., documentation, examples)
        // Our safety threshold is >= 3 patterns before we attempt fixing
        $parser = new Parser();
        $csv = $parser->fromFile(__DIR__ . '/data/encoding/legitimate_utf8_with_patterns.csv');
        $rows = $parser->toArray($csv);

        $this->assertEquals(4, count($rows));

        // Verify the mojibake patterns are preserved exactly as-is
        // Row 0: Only 2 distinct patterns (Ã¤, Ã¶) - below threshold, should be preserved
        $this->assertStringContainsString('Ã¤', $rows[0]['Pattern']);
        $this->assertStringContainsString('Ã¶', $rows[0]['Pattern']);

        // Row 1: Patterns should be preserved
        $this->assertNotEmpty($rows[1]['Pattern']);

        // Row 2: French accent patterns should be preserved
        $this->assertStringContainsString('Ã©', $rows[2]['Pattern']);
        $this->assertStringContainsString('Ã¨', $rows[2]['Pattern']);

        // Row 3: Spanish patterns should be preserved
        $this->assertStringContainsString('Ã­', $rows[3]['Pattern']);
        $this->assertStringContainsString('Ã³', $rows[3]['Pattern']);

        // Most importantly: verify the Notes column explains these are intentional
        // This proves we didn't corrupt legitimate documentation
        $this->assertStringContainsString('intentional', $rows[0]['Notes']);
        $this->assertStringContainsString('examples', $rows[1]['Notes']);
    }
}
