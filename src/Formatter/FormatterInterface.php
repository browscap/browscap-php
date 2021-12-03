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
     */
    public function setData(array $settings): void;

    /**
     * Gets the data (in the preferred format)
     *
     * @throws void
     */
    public function getData(): stdClass;
}
