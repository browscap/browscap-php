<?php
declare(strict_types = 1);

namespace BrowscapPHP\Parser;

use BrowscapPHP\Formatter\FormatterInterface;

/**
 * the interface for the ini parser class
 */
interface ParserInterface
{
    /**
     * Gets the browser data formatter for the given user agent
     * (or null if no data available, no even the default browser)
     *
     * @param  string                  $userAgent
     *
     * @throws \UnexpectedValueException
     *
     * @return FormatterInterface|null
     */
    public function getBrowser(string $userAgent) : ?FormatterInterface;
}
