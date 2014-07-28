<?php
namespace phpbrowscap\Parser\Helper;

use phpbrowscap\Cache\BrowscapCache;

/**
 * Ini parser class (compatible with PHP 5.5+)
 *
 * This parser overwrites parts of the basic ini parser class to use special
 * features form PHP 5.5 (generators) to optimize memory usage and performance.
 *
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Christoph Ziegenberg <christoph@ziegenberg.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @package phpbrowscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @copyright Copyright (c) 2014 Christoph Ziegenberg <christoph@ziegenberg.com>
 * @version 0.1
 * @license http://www.opensource.org/licenses/MIT MIT License
 * @link https://github.com/crossjoin/browscap
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
