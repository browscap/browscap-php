<?php

declare(strict_types=1);

namespace BrowscapPHP\Parser\Helper;

use UnexpectedValueException;

/**
 * interface for the parser dataHelper
 */
interface GetDataInterface
{
    /**
     * Gets the settings for a given pattern (method calls itself to
     * get the data from the parent patterns)
     *
     * @param  string[] $settings
     *
     * @return string[]
     *
     * @throws UnexpectedValueException
     *
     * @no-named-arguments
     */
    public function getSettings(string $pattern, array $settings = []): array;
}
