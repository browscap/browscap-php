<?php
declare(strict_types = 1);

namespace BrowscapPHP\IniParser;

use BrowscapPHP\Data\PropertyFormatter;
use BrowscapPHP\Data\PropertyHolder;
use BrowscapPHP\Helper\Quoter;
use BrowscapPHP\Parser\Helper\Pattern;
use BrowscapPHP\Parser\Helper\SubKey;

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
