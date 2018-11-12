<?php
declare(strict_types = 1);

namespace BrowscapPHP\IniParser;

use BrowscapPHP\Parser\Helper\Pattern;

/**
 * Ini parser class (compatible with PHP 5.3+)
 */
interface ParserInterface
{
    /**
     * Creates new ini part cache files
     *
     * @param string $content
     *
     * @throws \OutOfRangeException
     *
     * @return \Generator
     */
    public function createIniParts(string $content) : \Generator;

    /**
     * Creates new pattern cache files
     *
     * @param string $content
     *
     * @return \Generator
     */
    public function createPatterns($content) : \Generator;
}
