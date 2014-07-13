<?php
namespace phpbrowscap\Parser;

use phpbrowscap\Cache\CacheInterface;

/**
 * Abstract parser class
 *
 * The parser is the component, that parses a specific type of browscap source
 * data for the browser settings of a given user agent.
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
abstract class AbstractParser
{
    /**
     * Detected browscap version (read from INI file)
     *
     * @var int
     */
    protected static $version;

    /**
     * The cache instance
     *
     * @var \phpbrowscap\Cache\\phpbrowscap\Cache\CacheInterface
     */
    protected static $cache;

    /**
     * The type to use when downloading the browscap source data,
     * has to be overwritten by the extending class,
     * e.g. 'PHP_BrowscapINI'.
     *
     * @see http://browscap.org/
     * @var string
     */
    protected $sourceType = '';

    /**
     * Gets the type of source to use
     *
     * @return string
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * Gets the version of the Browscap data
     *
     * @return int
     */
    abstract public function getVersion();

    /**
     * Gets the browser data formatr for the given user agent
     * (or null if no data avaailble, no even the default browser)
     *
     * @param string $user_agent
     * @return \phpbrowscap\Formatter\FormatterInterface|null
     */
    abstract public function getBrowser($user_agent);

    /**
     * Resets cached data (e.g. the version) after an update of the source
     */
    public static function resetCachedData()
    {
        self::$version = null;
    }
}