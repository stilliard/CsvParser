<?php

namespace CsvParser\Reader;

class FileReader implements ReaderInterface
{
    public static bool $fixEncoding = true;

    public static function read(\CsvParser\Parser $parser, $file)
    {
        $contents = file_get_contents($file);

        // remove UTF-8 BOM that excel can add
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        // Fix encoding issues including double-encoding
        if (self::$fixEncoding) {
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
            if ($encoding && $encoding !== 'UTF-8') {
                $contents = iconv($encoding, 'UTF-8//IGNORE', $contents);
            } elseif (!$encoding) {
                // If detection fails, assume Windows-1252 as it's most common
                $contents = iconv('Windows-1252', 'UTF-8//IGNORE', $contents);
            }
        } else {
            // Content appears to be UTF-8, but check for double-encoding patterns
            if (self::hasDoubleEncoding($contents)) {
                // Try to fix double-encoding
                $encodings = ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15'];
                
                foreach ($encodings as $encoding) {
                    $attempt = iconv('UTF-8', $encoding.'//IGNORE', $contents);
                    $attempt = iconv($encoding, 'UTF-8//IGNORE', $attempt);
                    
                    // Check if this fixed the double-encoding
                    if (!self::hasDoubleEncoding($attempt)) {
                        $contents = $attempt;
                        break;
                    }
                }
            }
        }
        
        // Final cleanup - ensure valid UTF-8
        $contents = iconv('UTF-8', 'UTF-8//IGNORE', $contents);
        
        return $contents;
    }
    
    private static function hasDoubleEncoding(string $string): bool
    {
        // Common double-encoded patterns
        $patterns = [
            // From ISO-8859-1/Windows-1252
            'Ã¤', 'Ã¶', 'Ã¼', 'Ã„', 'Ã–', 'Ã‚', 'ÃŸ',
            'Ã©', 'Ã¨', 'Ã¡', 'Ã ', 'Ã­', 'Ã³', 'Ã±', 'Ã§',
            // Windows-1252 specific (smart quotes, em dash, etc.)
            'â€œ', 'â€', 'â€™', 'â€˜', 'â€"', 'â€"', 'â€¦',
            // Other patterns
            'Ã‚Â', 'Ã¢â‚¬'
        ];
        
        foreach ($patterns as $pattern) {
            if (strpos($string, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
