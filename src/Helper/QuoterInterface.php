<?php

declare(strict_types=1);

namespace BrowscapPHP\Helper;

use UnexpectedValueException;

/**
 * class to help quoting strings for using a regex
 */
interface QuoterInterface
{
    /**
     * Converts browscap match patterns into preg match patterns.
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function pregQuote(string $useragent, string $delimiter = '/'): string;

    /**
     * Reverts the quoting of a pattern.
     *
     * @throws UnexpectedValueException
     *
     * @no-named-arguments
     */
    public function pregUnQuote(string $pattern): string;
}
