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
 * @package    Browscap
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */

namespace phpbrowscap;

use phpbrowscap\Helper\Converter;
use phpbrowscap\Cache\BrowscapCache;
use WurflCache\Adapter\File;
use WurflCache\Adapter\AdapterInterface;
use phpbrowscap\Helper\IniLoader;
use phpbrowscap\Exception\FetcherException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Browscap.ini parsing class with caching and update capabilities
 *
 * @category   Browscap-PHP
 * @package    Browscap
 * @author     Jonathan Stoppani <jonathan@stoppani.name>
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @author     Mikołaj Misiurewicz <quentin389+phpb@gmail.com>
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class Browscap
{
    /**
     * Current version of the class.
     */
    const VERSION = '3.0a';

    /**
     * Parser to use
     *
     * @var \phpbrowscap\Parser\ParserInterface
     */
    private $parser = null;

    /**
     * Formatter to use
     *
     * @var \phpbrowscap\Formatter\FormatterInterface
     */
    private $formatter = null;

    /**
     * The cache instance
     *
     * @var \phpbrowscap\Cache\BrowscapCache
     */
    private $cache = null;

    /** @var \Psr\Log\LoggerInterface */
    private $logger = null;

    /**
     * Options for the updater. The array should be overwritten,
     * containing all options as keys, set to the default value.
     *
     * @var array
     */
    private $options = array();

    /**
     * Set theformatter instance to use for the getBrowser() result
     *
     * @param \phpbrowscap\Formatter\FormatterInterface $formatter
     *
     * @return \phpbrowscap\Browscap
     */
    public function setFormatter(Formatter\FormatterInterface $formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @return \phpbrowscap\Formatter\FormatterInterface
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
     * @return \phpbrowscap\Cache\BrowscapCache
     */
    public function getCache()
    {
        if (null === $this->cache) {
            $resourceDirectory = __DIR__  . '/../resources/';

            $cacheAdapter = new File(
                array(File::DIR => $resourceDirectory)
            );

            $this->cache = new BrowscapCache($cacheAdapter);
        }

        return $this->cache;
    }

    /**
     * Sets a cache instance
     *
     * @param \phpbrowscap\Cache\BrowscapCache|\WurflCache\Adapter\AdapterInterface $cache
     *
     * @throws \phpbrowscap\Exception
     * @return \phpbrowscap\Browscap
     */
    public function setCache($cache)
    {
        if ($cache instanceof BrowscapCache) {
            $this->cache = $cache;
        } elseif ($cache instanceof AdapterInterface) {
            $this->cache = new BrowscapCache($cache);
        } else {
            throw new Exception(
                'the cache has to be an instance of \phpbrowscap\Cache\BrowscapCache or '
                . 'an instanceof of \WurflCache\Adapter\AdapterInterface',
                Exception::CACHE_INCOMPATIBLE
            );
        }

        return $this;
    }

    /**
     * Sets the parser instance to use
     *
     * @param \phpbrowscap\Parser\ParserInterface $parser
     *
     * @return \phpbrowscap\Browscap
     */
    public function setParser(Parser\ParserInterface $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * returns an instance of the used parser class
     *
     * @return Parser\ParserInterface
     */
    public function getParser()
    {
        if (null === $this->parser) {
            $this->setParser(new Parser\Ini());
        }

        // generators are supported from PHP 5.5, so select the correct parser version to use
        // (the version without generators requires about 2-3x the memory and is a bit slower)
        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            $helper = new Parser\Helper\GetPattern();
        } else {
            $helper = new Parser\Helper\GetPatternLt55();
        }

        $helper->setCache($this->getCache());

        $this->parser
            ->setHelper($helper)
            ->setFormatter($this->getFormatter())
            ->setCache($this->getCache())
            ->setLogger($this->getLogger())
        ;

        return $this->parser;
    }

    /**
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \phpbrowscap\Browscap
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
     * Sets multiple loader options at once
     *
     * @param array $options
     *
     * @return \phpbrowscap\Browscap
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * parses the given user agent to get the information about the browser
     *
     * if no user agent is given, it uses {@see \phpbrowscap\Helper\Support} to get it
     *
     * @param string $userAgent the user agent string
     *
     * @throws \phpbrowscap\Exception
     * @return \stdClass  the object containing the browsers details. Array if
     *                    $return_array is set to true.
     */
    public function getBrowser($userAgent = null)
    {
        // Automatically detect the useragent
        if (!isset($userAgent)) {
            $support   = new Helper\Support($_SERVER);
            $userAgent = $support->getUserAgent();
        }

        // try to get browser data
        $return = $this->getParser()->getBrowser($userAgent);

        // if return is still NULL, updates are disabled... in this
        // case we return an empty formatter instance
        if ($return === null) {
            return $this->getFormatter()->getData();
        }

        return $return->getData();
    }

    /**
     * reads and parses an ini file and writes the results into the cache
     *
     * @param string $iniFile
     * @throws \phpbrowscap\Exception\FileNotFoundException
     */
    public function convertFile($iniFile)
    {
        $loader = new IniLoader();
        $loader->setLocalFile($iniFile);

        $converter = new Converter();

        $converter
            ->setLogger($this->getLogger())
            ->setCache($this->getCache())
            ->convertString($loader->load())
        ;
    }

    /**
     * reads and parses an ini string and writes the results into the cache
     *
     * @param string $iniString
     */
    public function convertString($iniString)
    {
        $converter = new Converter();

        $converter
            ->setLogger($this->getLogger())
            ->setCache($this->getCache())
            ->convertString($iniString)
        ;
    }

    /**
     * fetches a remote file and stores it into a local folder
     *
     * @param string $file       The name of the file where to store the remote content
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws \phpbrowscap\Exception\FetcherException
     */
    public function fetch($file, $remoteFile = IniLoader::PHP_INI)
    {
        $loader = new IniLoader();
        $loader
            ->setRemoteFilename($remoteFile)
            ->setOptions($this->options)
            ->setLogger($this->getLogger())
        ;

        $this->getLogger()->debug('started fetching remote file');

        $content = $loader->load();

        if ($content === false) {
            $error = error_get_last();
            throw FetcherException::httpError($loader->getRemoteIniUrl(), $error['message']);
        }

        $this->getLogger()->debug('finished fetching remote file');
        $this->getLogger()->debug('started storing remote file into local file');

        $fs = new Filesystem();
        $fs->dumpFile($file, $content);

        $this->getLogger()->debug('finished storing remote file into local file');
    }

    /**
     * fetches a remote file, parses it and writes the result into the cache
     *
     * if the local stored information are in the same version as the remote data no actions are
     * taken
     *
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws \phpbrowscap\Exception\FetcherException
     */
    public function update($remoteFile = IniLoader::PHP_INI)
    {
        $loader = new IniLoader();
        $loader
            ->setRemoteFilename($remoteFile)
            ->setOptions($this->options)
            ->setLogger($this->getLogger())
        ;

        $this->getLogger()->debug('started fetching remote file');

        $internalLoader = $loader->getLoader();

        $converter = new Converter();

        $converter
            ->setLogger($this->getLogger())
            ->setCache($this->getCache())
        ;

        $cachedVersion = $this->getCache()->getItem('browscap.version', false);
        $cachedTime    = $this->getCache()->getItem('browscap.time', false);
        $remoteTime    = $loader->getMTime();

        if ($remoteTime <= $cachedTime) {
            // no newer version available
            return;
        }

        $content = $loader->load();

        if ($content === false) {
            $error = error_get_last();
            throw FetcherException::httpError($internalLoader->getUri(), $error['message']);
        }

        $this->getLogger()->debug('finished fetching remote file');

        $iniVersion = $converter->getIniVersion($content);

        if ($iniVersion > $cachedVersion) {
            $converter
                ->storeVersion()
                ->convertString($content)
            ;
        }
    }
}
