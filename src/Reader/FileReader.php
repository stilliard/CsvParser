<?php

namespace CsvParser\Reader;

use Exception;

class FileReader implements ReaderInterface
{
    protected static $options = [
        'fixEncoding' => false, // requires opt-in to fix encoding issues
    ];

    public static function setDefaultOptions(array $options)
    {
        self::$options = array_merge(self::$options, $options);
    }

    public static function read(\CsvParser\Parser $parser, $file, array $options = [])
    {
        $options = array_merge(self::$options, $options);

        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new Exception("Could not open file: " . basename($file));
        }

        // remove UTF-8 BOM that excel can add
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        // Fix encoding issues including double-encoding
        if ($options['fixEncoding']) {
            $contents = self::fixEncodingIssues($contents);
        }

        return $parser->fromString($contents);
    }

    private static function fixEncodingIssues(string $contents): string
    {
        // Check for UTF-16 BOM (Excel on Mac sometimes uses this)
        if (strlen($contents) >= 2) {
            $bom = substr($contents, 0, 2);
            if ($bom === "\xFF\xFE") {
                // UTF-16LE BOM
                $converted = iconv('UTF-16LE', 'UTF-8//IGNORE', $contents);
                if ($converted !== false) {
                    // Remove the UTF-8 BOM that results from converting the UTF-16 BOM
                    return preg_replace('/^\xEF\xBB\xBF/', '', $converted);
                }
            } elseif ($bom === "\xFE\xFF") {
                // UTF-16BE BOM
                $converted = iconv('UTF-16BE', 'UTF-8//IGNORE', $contents);
                if ($converted !== false) {
                    // Remove the UTF-8 BOM that results from converting the UTF-16 BOM
                    return preg_replace('/^\xEF\xBB\xBF/', '', $converted);
                }
            }
        }

        // Check if the content is valid UTF-8
        if (! mb_check_encoding($contents, 'UTF-8')) {
            // mb_detect_encoding is not reliable for distinguishing between similar encodings
            // Try each encoding and pick the best result based on whether conversion succeeds
            // and produces valid UTF-8 without replacement characters
            $encodings = ['Windows-1252', 'ISO-8859-15', 'ISO-8859-1'];
            $bestResult = null;
            $bestScore = -1;

            foreach ($encodings as $encoding) {
                $converted = iconv($encoding, 'UTF-8//TRANSLIT', $contents);
                if ($converted === false) {
                    continue;
                }

                // Score this conversion based on:
                // 1. Valid UTF-8
                // 2. Fewer replacement characters (�)
                // 3. Presence of common special characters that indicate good conversion
                $score = 0;

                if (mb_check_encoding($converted, 'UTF-8')) {
                    $score += 100;
                }

                // Penalize replacement characters
                $replacementCount = substr_count($converted, '�');
                $score -= $replacementCount * 10;

                // Reward presence of properly converted special characters
                // These indicate the encoding was likely correct
                if (preg_match('/[€™©®–—\x{201C}\x{201D}\x{2018}\x{2019}\x{2026}\x{2022}]/u', $converted)) {
                    $score += 50;
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestResult = $converted;
                }
            }

            if ($bestResult !== null) {
                $contents = $bestResult;
            }
        } else {
            // Content appears to be UTF-8, but check for double-encoding patterns
            if (self::hasDoubleEncoding($contents)) {
                // Count mojibake patterns in the original
                $originalCount = self::countDoubleEncodingPatterns($contents);

                // Try to fix double-encoding
                $encodings = ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15'];

                foreach ($encodings as $encoding) {
                    $attempt = iconv('UTF-8', $encoding.'//IGNORE', $contents);
                    if ($attempt === false) {
                        continue;
                    }

                    $attempt = iconv($encoding, 'UTF-8//TRANSLIT', $attempt);
                    if ($attempt === false) {
                        continue;
                    }

                    // Only use the fix if it reduces mojibake patterns
                    $attemptCount = self::countDoubleEncodingPatterns($attempt);
                    if ($attemptCount < $originalCount) {
                        $contents = $attempt;
                        break;
                    }
                }
            }
        }

        // Final cleanup - ensure valid UTF-8
        $cleaned = iconv('UTF-8', 'UTF-8//IGNORE', $contents);
        if ($cleaned !== false) {
            // Log if any characters were dropped
            if (strlen($cleaned) < strlen($contents)) {
                error_log('[CSV] Warning: Dropped invalid UTF-8 sequences during encoding cleanup in FileReader.');
            }
            $contents = $cleaned;
        }

        return $contents;
    }

    private static function hasDoubleEncoding(string $string): bool
    {
        return self::countDoubleEncodingPatterns($string) > 0;
    }

    private static function countDoubleEncodingPatterns(string $string): int
    {
        // Common double-encoded patterns
        $patterns = [
            // From ISO-8859-1/Windows-1252
            'Ã¤', 'Ã¶', 'Ã¼', 'Ã„', 'Ã–', 'Ã‚', 'ÃŸ',
            'Ã©', 'Ã¨', 'Ã¡', 'Ã ', 'Ã­', 'Ã³', 'Ã±', 'Ã§',
            // Windows-1252 specific (smart quotes, en dash, em dash, ellipsis)
            'â€œ', 'â€', 'â€™', 'â€˜', 'â€“', 'â€”', 'â€¦',
            // Other patterns
            'Ã‚Â', 'Ã¢â‚¬'
        ];

        $count = 0;
        foreach ($patterns as $pattern) {
            $count += substr_count($string, $pattern);
        }

        return $count;
    }
}
