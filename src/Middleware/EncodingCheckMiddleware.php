<?php

namespace CsvParser\Middleware;

use InvalidArgumentException;
use RuntimeException;

class EncodingCheckMiddleware implements StringReaderMiddlewareInterface
{
    protected string $encoding = 'UTF-8';
    protected string $action = 'warn'; // warn, throw, fix
    protected string $fallbackEncoding = 'Windows-1252';
    protected bool $fixMojibake = false;

    public function __construct(array $options = [])
    {
        if (isset($options['encoding'])) {
            $this->encoding = $options['encoding'];
        }
        if (isset($options['action'])) {
            $this->action = $options['action'];
            if (! in_array($this->action, ['warn', 'throw', 'fix'])) {
                throw new InvalidArgumentException("Invalid action '{$this->action}'. Must be one of: warn, throw, fix");
            }
        }
        if (isset($options['fallbackEncoding'])) {
            $this->fallbackEncoding = $options['fallbackEncoding'];
        }
        if (isset($options['fixMojibake'])) {
            $this->fixMojibake = (bool) $options['fixMojibake'];
        }
    }

    public function read(array $row, array $context): array
    {
        foreach ($row as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (!mb_check_encoding($value, $this->encoding)) {
                $this->handleInvalidEncoding($row, $key, $value, $context['index'] ?? 0);
            } elseif ($this->fixMojibake) {
                $this->handleMojibake($row, $key, $value);
            }
        }
        return $row;
    }

    protected function handleMojibake(array &$row, $key, $value): void
    {
        // Try to convert the string "back" to the fallback encoding
        // (treating the current UTF-8 bytes as if they were the result of a bad conversion)
        $fixed = mb_convert_encoding($value, $this->fallbackEncoding, $this->encoding);

        // Check if the result is valid UTF-8
        // If the conversion worked and resulted in valid UTF-8, it was likely double-encoded.
        if (mb_check_encoding($fixed, $this->encoding) && $fixed !== $value) {
            $row[$key] = $fixed;
        }
    }

    protected function handleInvalidEncoding(array &$row, $key, $value, $line): void
    {
        switch ($this->action) {
            case 'throw':
                throw new RuntimeException("Invalid encoding detected in row for key '{$key}' on line {$line}. Expected {$this->encoding}.");
            case 'fix':
                // Try to fix by converting from fallback encoding
                $fixed = mb_convert_encoding($value, $this->encoding, $this->fallbackEncoding);
                // If that didn't result in valid encoding (unlikely if fallback is single-byte), or if we want to be sure
                if (mb_check_encoding($fixed, $this->encoding)) {
                    $row[$key] = $fixed;
                } else {
                    // If still invalid, maybe scrub?
                    $row[$key] = mb_scrub($value, $this->encoding);
                }
                break;
            case 'warn':
            default:
                trigger_error("Invalid encoding detected in row for key '{$key}' on line {$line}. Expected {$this->encoding}.", E_USER_WARNING);
                break;
        }
    }
}
