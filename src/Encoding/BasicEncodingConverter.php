<?php

namespace CsvParser\Encoding;

/**
 * Basic encoding converter that handles common encoding issues
 *
 * EXPERIMENTAL FEATURES:
 * - Double-encoding (mojibake) detection and fixing is EXPERIMENTAL
 *   and may not work reliably in all cases. Use with caution.
 */
class BasicEncodingConverter implements EncodingConverterInterface
{
    use DoubleEncodingDetectionTrait;
    /**
     * Convert content to UTF-8
     *
     * Handles:
     * - UTF-16 BOM detection (LE and BE)
     * - Multi-encoding detection (Windows-1252, ISO-8859-15, ISO-8859-1)
     * - Double-encoding fixes (EXPERIMENTAL)
     *
     * @param string $contents Raw file contents in any encoding
     * @return string UTF-8 encoded content
     */
    public function convert(string $contents): string
    {
        // Remove UTF-8 BOM that excel can add
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

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
            // EXPERIMENTAL: This feature is experimental and may not work reliably
            if ($this->hasDoubleEncoding($contents)) {
                // Count mojibake patterns in the original
                $originalCount = $this->countDoubleEncodingPatterns($contents);

                // Safety threshold: Only attempt to fix if there are significant mojibake patterns
                // This prevents us from "fixing" legitimate UTF-8 content that happens to contain
                // mojibake-like strings (e.g., documentation about encoding issues)
                if ($originalCount >= 3) {
                    // Try to fix double-encoding
                    $encodings = ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15'];
                    $bestFix = null;
                    $bestCount = $originalCount;

                    foreach ($encodings as $encoding) {
                        $attempt = iconv('UTF-8', $encoding.'//IGNORE', $contents);
                        if ($attempt === false) {
                            continue;
                        }

                        $attempt = iconv($encoding, 'UTF-8//TRANSLIT', $attempt);
                        if ($attempt === false) {
                            continue;
                        }

                        // Only use the fix if it significantly reduces mojibake patterns
                        // We want at least a 50% reduction to be confident it's actual double-encoding
                        $attemptCount = $this->countDoubleEncodingPatterns($attempt);
                        if ($attemptCount < $bestCount && $attemptCount <= $originalCount / 2) {
                            $bestCount = $attemptCount;
                            $bestFix = $attempt;
                        }
                    }

                    if ($bestFix !== null) {
                        $contents = $bestFix;
                    }
                }
            }
        }

        // Final cleanup - ensure valid UTF-8
        $cleaned = iconv('UTF-8', 'UTF-8//IGNORE', $contents);
        if ($cleaned !== false) {
            // Log if any characters were dropped
            if (strlen($cleaned) < strlen($contents)) {
                error_log('[CSV] Warning: Dropped invalid UTF-8 sequences during encoding cleanup in BasicEncodingConverter.');
            }
            $contents = $cleaned;
        }

        return $contents;
    }
}
