<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
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
use Psr\Log\LoggerInterface;

/**
 * extracts the pattern and the data for theses pattern from the ini content,
 * optimized for PHP 5.3 and PHP 5.4
 *
 * @category   Browscap-PHP
 * @package    Parser\Helper
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class GetPatternLt55 implements GetPatternInterface
{
    /**
     * The cache instance
     *
     * @var \phpbrowscap\Cache\BrowscapCache
     */
    private $cache = null;

    /** @var \Psr\Log\LoggerInterface */
    private $logger = null;

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
     * @return \phpbrowscap\Parser\Helper\GetPatternLt55
     */
    public function setCache(BrowscapCache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \phpbrowscap\Parser\Helper\GetPatternLt55
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Returns a logger instance
     *
     * @return \Psr\Log\LoggerInterface $logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Gets some possible patterns that have to be matched against the user agent. With the given
     * user agent string, we can optimize the search for potential patterns:
     * - We check the first characters of the user agent (or better: a hash, generated from it)
     * - We compare the length of the pattern with the length of the user agent
     *   (the pattern cannot be longer than the user agent!)
     *
     * @return array
     */
    public function getPatterns()
    {
        $success = null;
        $count   = $this->getCache()->getItem('browscap.patterns.count', true, $success);

        if (!$success) {
            $this->getLogger()->debug('cache key "browscap.patterns.count" not found');
            return array();
        }

        $keyLength  = strlen((string) $count);
        $patternarr = array();

        for ($index = 0; $index <= $count; $index++) {
            $success = null;
            $key     = str_pad($index, $keyLength, '0', STR_PAD_LEFT);

            $this->getLogger()->debug('search key "browscap.patterns.' . $key . '"');

            $file = $this->getCache()->getItem('browscap.patterns.' . $key, true, $success);

            if (!$success) {
                $this->getLogger()->info('cache key "browscap.patterns.' . $key . '" not found');
                continue;
            }

            foreach ($file as $buffer) {
                $patternarr[] = trim($buffer);
            }
        }

        return $patternarr;
    }
}
