<?php

namespace CsvParser\Encoding;

use Exception;

/**
 * Strict UTF-8 converter that throws exceptions on invalid UTF-8
 *
 * This converter enforces that input is already valid UTF-8.
 * It will throw an exception if the content is not valid UTF-8,
 * making it ideal for environments where you want to fail fast
 * and force users to provide properly encoded files.
 */
class StrictUtf8Converter implements EncodingConverterInterface
{
    use DoubleEncodingDetectionTrait;
    /**
     * Validate that content is UTF-8, throw exception otherwise
     *
     * @param string $contents Content to validate
     * @return string The same content if valid UTF-8
     * @throws Exception If content is not valid UTF-8
     */
    public function convert(string $contents): string
    {
        // Remove UTF-8 BOM if present (this is acceptable)
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        // Check if the content is valid UTF-8
        if (!mb_check_encoding($contents, 'UTF-8')) {
            throw new Exception(
                'File is not encoded in UTF-8. Please convert your file to UTF-8 before importing. ' .
                'Common tools: iconv, dos2unix, or save as UTF-8 in your editor.'
            );
        }

        // Check for common double-encoding patterns that indicate mojibake
        if ($this->hasDoubleEncoding($contents)) {
            throw new Exception(
                'File appears to contain double-encoded (mojibake) characters. ' .
                'This usually means the file was incorrectly converted to UTF-8. ' .
                'Please re-save the original file with proper UTF-8 encoding.'
            );
        }

        return $contents;
    }

    /**
     * Check if string contains double-encoding patterns
     *
     * Overrides the trait method to use a stricter threshold (>= 3 patterns)
     *
     * @param string $string Content to check
     * @return bool True if double-encoding patterns detected
     */
    private function hasDoubleEncoding(string $string): bool
    {
        return $this->countDoubleEncodingPatterns($string) >= 3;
    }
}
