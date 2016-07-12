<?php
/**
 * Copyright (c) 1998-2015 Browser Capabilities Project
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
 * @copyright  1998-2015 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */

namespace BrowscapPHP;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Helper\Quoter;
use BrowscapPHP\Parser\ParserInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WurflCache\Adapter\AdapterInterface;
use WurflCache\Adapter\File;

/**
 * Browscap.ini parsing class with caching and update capabilities
 *
 * @category   Browscap-PHP
 * @author     Jonathan Stoppani <jonathan@stoppani.name>
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @author     Mikołaj Misiurewicz <quentin389+phpb@gmail.com>
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class Browscap
{
    /**
     * Parser to use
     *
     * @var \BrowscapPHP\Parser\ParserInterface
     */
    private $parser = null;

    /**
     * Formatter to use
     *
     * @var \BrowscapPHP\Formatter\FormatterInterface
     */
    private $formatter = null;

    /**
     * The cache instance
     *
     * @var \BrowscapPHP\Cache\BrowscapCacheInterface
     */
    private $cache = null;

    /**
     * @var @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * Set theformatter instance to use for the getBrowser() result
     *
     * @param \BrowscapPHP\Formatter\FormatterInterface $formatter
     *
     * @return \BrowscapPHP\Browscap
     */
    public function setFormatter(Formatter\FormatterInterface $formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @return \BrowscapPHP\Formatter\FormatterInterface
     */
    public function getFormatter()
    {
        if (null === $this->formatter) {
            $this->setFormatter(new Formatter\PhpGetBrowser());
        }

        return $this->formatter;
    }

    /**
     * Gets a cache instance
     *
     * @return \BrowscapPHP\Cache\BrowscapCacheInterface
     */
    public function getCache()
    {
        if (null === $this->cache) {
            $cacheDirectory = __DIR__ . '/../resources/';

            $cacheAdapter = new File(
                [File::DIR => $cacheDirectory]
            );

            $this->cache = new BrowscapCache($cacheAdapter);
        }

        return $this->cache;
    }

    /**
     * Sets a cache instance
     *
     * @param \BrowscapPHP\Cache\BrowscapCacheInterface|\WurflCache\Adapter\AdapterInterface $cache
     *
     * @throws \BrowscapPHP\Exception
     * @return \BrowscapPHP\Browscap
     */
    public function setCache($cache)
    {
        if ($cache instanceof BrowscapCacheInterface) {
            $this->cache = $cache;
        } elseif ($cache instanceof AdapterInterface) {
            $this->cache = new BrowscapCache($cache);
        } else {
            throw new Exception(
                'the cache has to be an instance of \BrowscapPHP\Cache\BrowscapCacheInterface or '
                . 'an instanceof of \WurflCache\Adapter\AdapterInterface',
                Exception::CACHE_INCOMPATIBLE
            );
        }

        return $this;
    }

    /**
     * Sets the parser instance to use
     *
     * @param \BrowscapPHP\Parser\ParserInterface $parser
     *
     * @return \BrowscapPHP\Browscap
     */
    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * returns an instance of the used parser class
     *
     * @return \BrowscapPHP\Parser\ParserInterface
     */
    public function getParser()
    {
        if (null === $this->parser) {
            $cache  = $this->getCache();
            $logger = $this->getLogger();
            $quoter = new Quoter();

            $patternHelper = new Parser\Helper\GetPattern($cache, $logger);
            $dataHelper    = new Parser\Helper\GetData($cache, $logger, $quoter);

            $this->parser = new Parser\Ini($patternHelper, $dataHelper, $this->getFormatter());
        }

        return $this->parser;
    }

    /**
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \BrowscapPHP\Browscap
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * returns a logger instance
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * parses the given user agent to get the information about the browser
     *
     * if no user agent is given, it uses {@see \BrowscapPHP\Helper\Support} to get it
     *
     * @param string $userAgent the user agent string
     *
     * @throws \BrowscapPHP\Exception
     * @return \stdClass              the object containing the browsers details. Array if
     *                                $return_array is set to true.
     */
    public function getBrowser($userAgent = null)
    {
        if (null === $this->getCache()->getVersion()) {
            // there is no active/warm cache available
            throw new Exception('there is no active cache available, please run the update command');
        }

        // Automatically detect the useragent
        if (!isset($userAgent)) {
            $support   = new Helper\Support($_SERVER);
            $userAgent = $support->getUserAgent();
        }

        // try to get browser data
        $formatter = $this->getParser()->getBrowser($userAgent);

        // if return is still NULL, updates are disabled... in this
        // case we return an empty formatter instance
        if ($formatter === null) {
            return $this->getFormatter()->getData();
        }

        return $formatter->getData();
    }
}
