<?php

namespace CsvParser\Encoding;

/**
 * Shared trait for detecting double-encoding (mojibake) patterns
 *
 * Double-encoding occurs when text is encoded multiple times, typically
 * when non-UTF-8 text is incorrectly interpreted as UTF-8 and re-encoded.
 */
trait DoubleEncodingDetectionTrait
{
    /**
     * Check if string contains double-encoding patterns
     *
     * @param string $string Content to check
     * @return bool True if double-encoding patterns detected
     */
    private function hasDoubleEncoding(string $string): bool
    {
        return $this->countDoubleEncodingPatterns($string) > 0;
    }

    /**
     * Count mojibake patterns in a string
     *
     * Common double-encoded patterns for UK/FR/DE/ES locales.
     * These patterns appear when Latin-1/Windows-1252 text is
     * incorrectly treated as UTF-8.
     *
     * @param string $string Content to check
     * @return int Number of mojibake patterns found
     */
    private function countDoubleEncodingPatterns(string $string): int
    {
        // Common double-encoded patterns for UK/FR/DE/ES locales
        $patterns = [
            // German umlauts and ß (ä, ö, ü, Ä, Ö, Ü, ß)
            'Ã¤', 'Ã¶', 'Ã¼', 'Ã„', 'Ã–', 'Ã‚', 'ÃŸ',

            // French accented characters (é, è, ê, à, ç, ô, î, â, ù, û, ë, ï)
            'Ã©', 'Ã¨', 'Ãª', 'Ã ', 'Ã§', 'Ã´', 'Ã®', 'Ã¢', 'Ã¹', 'Ã»', 'Ã«', 'Ã¯',

            // Spanish accented characters (á, é, í, ó, ú, ñ, ü, ¿, ¡)
            'Ã¡', 'Ã­', 'Ã³', 'Ãº', 'Ã±', 'Â¿', 'Â¡',

            // Currency and common symbols (€, £, ©, ®, ™)
            'â‚¬', 'Â£', 'Â©', 'Â®', 'â„¢',

            // Windows-1252 specific (smart quotes, en dash, em dash, ellipsis, bullet)
            'â€œ', 'â€', 'â€™', 'â€˜', 'â€"', 'â€"', 'â€¦', 'â€¢',

            // Other common double-encoding artifacts
            'Ã‚Â', 'Ã¢â‚¬'
        ];

        $count = 0;
        foreach ($patterns as $pattern) {
            $count += substr_count($string, $pattern);
        }

        return $count;
    }
}
