<?php

declare(strict_types=1);

namespace BrowscapPHP;

use BrowscapPHP\Parser\ParserInterface;
use stdClass;

/**
 * Browscap.ini parsing class with caching and update capabilities
 */
interface BrowscapInterface
{
    /**
     * Set theformatter instance to use for the getBrowser() result
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function setFormatter(Formatter\FormatterInterface $formatter): void;

    /**
     * Sets the parser instance to use
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function setParser(ParserInterface $parser): void;

    /**
     * returns an instance of the used parser class
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function getParser(): ParserInterface;

    /**
     * parses the given user agent to get the information about the browser
     *
     * if no user agent is given, it uses {@see \BrowscapPHP\Helper\Support} to get it
     *
     * @param string $userAgent the user agent string
     *
     * @return stdClass the object containing the browsers details.
     *
     * @throws Exception
     *
     * @no-named-arguments
     */
    public function getBrowser(?string $userAgent = null): stdClass;
}
