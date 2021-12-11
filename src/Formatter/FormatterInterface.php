<?php

declare(strict_types=1);

namespace BrowscapPHP\Formatter;

use stdClass;

/**
 * interface for formating the output
 */
interface FormatterInterface
{
    /**
     * Sets the data (done by the parser)
     *
     * @param string[]|bool[] $settings
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function setData(array $settings): void;

    /**
     * Gets the data (in the preferred format)
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function getData(): stdClass;
}
