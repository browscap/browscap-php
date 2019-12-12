<?php
declare(strict_types = 1);

namespace BrowscapPHP;

use BrowscapPHP\Parser\ParserInterface;

/**
 * Browscap.ini parsing class with caching and update capabilities
 */
interface BrowscapInterface
{
    /**
     * Set theformatter instance to use for the getBrowser() result
     *
     * @param \BrowscapPHP\Formatter\FormatterInterface $formatter
     */
    public function setFormatter(Formatter\FormatterInterface $formatter) : void;

    /**
     * Sets the parser instance to use
     *
     * @param \BrowscapPHP\Parser\ParserInterface $parser
     */
    public function setParser(ParserInterface $parser) : void;

    /**
     * returns an instance of the used parser class
     *
     * @return \BrowscapPHP\Parser\ParserInterface
     */
    public function getParser() : ParserInterface;

    /**
     * parses the given user agent to get the information about the browser
     *
     * if no user agent is given, it uses {@see \BrowscapPHP\Helper\Support} to get it
     *
     * @param string $userAgent the user agent string
     *
     * @throws \BrowscapPHP\Exception
     *
     * @return \stdClass              the object containing the browsers details.
     */
    public function getBrowser(?string $userAgent = null) : \stdClass;
}
