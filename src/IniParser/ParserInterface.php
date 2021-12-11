<?php

declare(strict_types=1);

namespace BrowscapPHP\IniParser;

use Generator;
use InvalidArgumentException;
use JsonException;
use OutOfRangeException;
use UnexpectedValueException;

/**
 * Ini parser class (compatible with PHP 5.3+)
 */
interface ParserInterface
{
    /**
     * Creates new ini part cache files
     *
     * @throws OutOfRangeException
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     * @throws JsonException
     *
     * @no-named-arguments
     */
    public function createIniParts(string $content): Generator;

    /**
     * Creates new pattern cache files
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function createPatterns(string $content): Generator;
}
