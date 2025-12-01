<?php

namespace CsvParser\Reader;

class FileReader implements ReaderInterface
{
    public static function read(\CsvParser\Parser $parser, $file, bool $fixEncoding = true)
    {
        $contents = file_get_contents($file);

        // remove UTF-8 BOM that excel can add
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        // Fix encoding issues including double-encoding
        if ($fixEncoding) {
            $contents = self::fixEncodingIssues($contents);
        }

        return $parser->fromString($contents);
    }

    private static function fixEncodingIssues(string $contents): string
    {
        // Check if the content is valid UTF-8
        if (! mb_check_encoding($contents, 'UTF-8')) {
            // Try to detect the encoding and convert
            // Note: Windows-1252 is a superset of ISO-8859-1, so try it first
            $encoding = mb_detect_encoding($contents, ['Windows-1252', 'ISO-8859-15', 'ISO-8859-1'], true);

            // mb_detect_encoding can't reliably distinguish between these encodings
            // Windows-1252 is the safest bet as it's a superset of ISO-8859-1
            // and handles special characters in the 0x80-0x9F range
            if (!$encoding || in_array($encoding, ['ISO-8859-1', 'ISO-8859-15'])) {
                $encoding = 'Windows-1252';
            }

            if ($encoding && $encoding !== 'UTF-8') {
                $converted = iconv($encoding, 'UTF-8//TRANSLIT', $contents);
                if ($converted !== false) {
                    $contents = $converted;
                }
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
