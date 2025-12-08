<?php

namespace CsvParser\Middleware;

class EncodingCheckMiddleware implements StringReaderMiddlewareInterface
{
    protected string $encoding = 'UTF-8';
    protected string $action = 'warn'; // warn, throw, fix
    protected string $fallbackEncoding = 'Windows-1252';

    public function __construct(array $options = [])
    {
        if (isset($options['encoding'])) {
            $this->encoding = $options['encoding'];
        }
        if (isset($options['action'])) {
            $this->action = $options['action'];
        }
        if (isset($options['fallbackEncoding'])) {
            $this->fallbackEncoding = $options['fallbackEncoding'];
        }
    }

    public function read(array $row, array $context): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value) && !mb_check_encoding($value, $this->encoding)) {
                $this->handleInvalidEncoding($row, $key, $value);
            }
        }
        return $row;
    }

    protected function handleInvalidEncoding(array &$row, $key, $value): void
    {
        switch ($this->action) {
            case 'throw':
                throw new \RuntimeException("Invalid encoding detected in row for key '$key'. Expected {$this->encoding}.");
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
                trigger_error("Invalid encoding detected in row for key '$key'. Expected {$this->encoding}.", E_USER_WARNING);
                break;
        }
    }
}
