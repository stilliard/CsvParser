<?php

namespace CsvParser\Middleware;

/**
 * Protect against formula injection
 *
 * ref https://owasp.org/www-community/attacks/CSV_Injection
 */
class FormulaInjectionMiddleware implements StringWriterMiddlewareInterface, StringReaderMiddlewareInterface
{
    protected string $injectionCharacters = '=+-@';

    use EscapeMiddlewareTrait;

    public function __construct(array $options = [])
    {
        if (isset($options['injectionCharacters'])) {
            $this->injectionCharacters = $options['injectionCharacters'];
        }
        if (isset($options['escapeChar'])) {
            $this->escapeChar = $options['escapeChar'];
        }

        $this->writePattern = '/^\s*[' . preg_quote($this->injectionCharacters, '/') . ']/';
        $this->readPattern = '/^' . preg_quote($this->escapeChar, '/') . '\s*[' . preg_quote($this->injectionCharacters, '/') . ']/';
    }
}
