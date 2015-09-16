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

namespace BrowscapPHP\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCache;
use Psr\Log\LoggerInterface;
use BrowscapPHP\Helper\Quoter;

/**
 * interface for the parser dataHelper
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
interface GetDataInterface
{
    /**
     * Gets a cache instance
     *
     * @return \BrowscapPHP\Cache\BrowscapCache
     */
    public function getCache();

    /**
     * Sets a cache instance
     *
     * @param \BrowscapPHP\Cache\BrowscapCache $cache
     *
     * @return \BrowscapPHP\Parser\Helper\GetDataInterface
     */
    public function setCache(BrowscapCache $cache);

    /**
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \BrowscapPHP\Parser\Helper\GetDataInterface
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * Returns a logger instance
     *
     * @return \Psr\Log\LoggerInterface $logger
     */
    public function getLogger();

    /**
     * @param \BrowscapPHP\Helper\Quoter $quoter
     *
     * @return \BrowscapPHP\Parser\Helper\GetDataInterface
     */
    public function setQuoter(Quoter $quoter);

    /**
     * @return \BrowscapPHP\Helper\Quoter
     */
    public function getQuoter();

    /**
     * Gets the settings for a given pattern (method calls itself to
     * get the data from the parent patterns)
     *
     * @param  string $pattern
     * @param  array  $settings
     * @return array
     */
    public function getSettings($pattern, array $settings = array());
}
