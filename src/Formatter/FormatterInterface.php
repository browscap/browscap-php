<?php
declare(strict_types = 1);

namespace BrowscapPHP\Formatter;

/**
 * interface for formating the output
 */
interface FormatterInterface
{
    /**
     * Sets the data (done by the parser)
     *
     * @param string[] $settings
     */
    public function setData(array $settings) : void;

    /**
     * Gets the data (in the preferred format)
     *
     * @return \stdClass
     */
    public function getData() : \stdClass;
}
