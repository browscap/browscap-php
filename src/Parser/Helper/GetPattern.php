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
 * @package    Parser\Helper
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Parser\Helper;

use phpbrowscap\Cache\BrowscapCache;

/**
 * extracts the pattern and the data for theses pattern from the ini content, optimized for PHP 5.5+
 *
 * @category   Browscap-PHP
 * @package    Parser\Helper
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class GetPattern implements GetPatternInterface
{
    /**
     * The cache instance
     *
     * @var \phpbrowscap\Cache\BrowscapCache
     */
    private $cache = null;

    /**
     * Gets a cache instance
     *
     * @return \phpbrowscap\Cache\BrowscapCache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Sets a cache instance
     *
     * @param \phpbrowscap\Cache\BrowscapCache $cache
     *
     * @return \phpbrowscap\Parser\Ini
     */
    public function setCache(BrowscapCache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Gets some possible patterns that have to be matched against the user agent. With the given
     * user agent string, we can optimize the search for potential patterns:
     * - We check the first characters of the user agent (or better: a hash, generated from it)
     * - We compare the length of the pattern with the length of the user agent
     *   (the pattern cannot be longer than the user agent!)
     *
     * @param string $user_agent
     *
     * @return \Generator
     */
    public function getPatterns($user_agent)
    {
        $start  = Pattern::getPatternStart($user_agent);
        $length = strlen($user_agent);
        $subkey = Pattern::getPatternCacheSubkey($start);

        // get patterns, first for the given browser and if that is not found,
        // for the default browser (with a special key)
        foreach (array($start, str_repeat('z', 32)) as $tmp_start) {
            $tmp_subkey = Pattern::getPatternCacheSubkey($tmp_start);
            $success    = null;
            
            $file = $this->getCache()->getItem('browscap.patterns.' . $tmp_subkey, true, $success);
            
            if (!$success) {
                continue;
            }
            
            $found = false;
            
            foreach ($file as $buffer) {
                $tmp_buffer = substr($buffer, 0, 32);
                if ($tmp_buffer === $tmp_start) {
                    // get length of the pattern
                    $len = (int)strstr(substr($buffer, 33, 4), ' ', true);

                    // the user agent must be longer than the pattern without place holders
                    if ($len <= $length) {
                        list(,,$patterns) = explode(" ", $buffer, 3);
                        yield trim($patterns);
                    }
                    $found = true;
                } elseif ($found === true) {
                    break;
                }
            }
        }
        yield false;
    }
}
