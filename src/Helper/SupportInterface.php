<?php
declare(strict_types = 1);

namespace BrowscapPHP\Helper;

/**
 * class to help getting the user agent
 */
interface SupportInterface
{
    /**
     * detect the useragent
     *
     * @return string
     */
    public function getUserAgent() : string;
}
