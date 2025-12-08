<?php

namespace CsvParser\Middleware;

/**
 * Detect dates and add escape character so that spreadsheet apps don't auto-convert formats
 *
 * ref https://support.microsoft.com/en-gb/office/stop-automatically-changing-numbers-to-dates-452bd2db-cc96-47d1-81e4-72cec11c4ed8
 */
class DatetimeMiddleware implements StringWriterMiddlewareInterface, StringReaderMiddlewareInterface
{
    protected string $pattern = '\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2})?'; // e.g. 2023-10-15 or 2023-10-15 14:30:00 or 2023-10-15T14:30:00

    use EscapeMiddlewareTrait;

    public function __construct(array $options = [])
    {
        if (isset($options['pattern'])) {
            $this->pattern = $options['pattern'];
        }
        if (isset($options['escapeChar'])) {
            $this->escapeChar = $options['escapeChar'];
        }

        $this->writePattern = '/^' . $this->pattern . '$/';
        $this->readPattern = '/^' . preg_quote($this->escapeChar, '/') . $this->pattern . '$/';
    }
}
