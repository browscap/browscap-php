<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Refer to the LICENSE file distributed with this package.
 *
 * @category   Browscap-PHP
 * @package    Parser
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Parser;

use phpbrowscap\Cache\BrowscapCache;
use phpbrowscap\Formatter\FormatterInterface;
use phpbrowscap\Parser\Helper\GetPatternInterface;

/**
 * the interface for the ini parser class
 *
 * @category   Browscap-PHP
 * @package    Parser
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
interface ParserInterface
{
    /**
     * @param \phpbrowscap\Parser\Helper\GetPatternInterface $helper
     *
     * @return \phpbrowscap\Parser\Ini
     */
    public function setHelper(GetPatternInterface $helper);

    /**
     * @return \phpbrowscap\Parser\Helper\GetPatternInterface
     */
    public function getHelper();

    /**
     * Set theformatter instance to use for the getBrowser() result
     *
     * @param \phpbrowscap\Formatter\FormatterInterface $formatter
     *
     * @return \phpbrowscap\Parser\Ini
     */
    public function setFormatter(FormatterInterface $formatter);

    /**
     * @return \phpbrowscap\Formatter\FormatterInterface
     */
    public function getFormatter();

    /**
     * Gets a cache instance
     *
     * @return \phpbrowscap\Cache\BrowscapCache
     */
    public function getCache();

    /**
     * Sets a cache instance
     *
     * @param \phpbrowscap\Cache\BrowscapCache $cache
     *
     * @return \phpbrowscap\Parser\Ini
     */
    public function setCache(BrowscapCache $cache);

    /**
     * Gets the browser data formatr for the given user agent
     * (or null if no data avaailble, no even the default browser)
     *
     * @param string $user_agent
     * @return FormatterInterface|null
     */
    public function getBrowser($user_agent);
}
