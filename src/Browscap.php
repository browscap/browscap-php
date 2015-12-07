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
 * @package    Browscap
 * @copyright  1998-2015 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */

namespace BrowscapPHP;

use Browscap\Generator\BuildGenerator;
use Browscap\Helper\CollectionCreator;
use Browscap\Writer\Factory\PhpWriterFactory;
use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Helper\Converter;
use BrowscapPHP\Helper\Filesystem;
use BrowscapPHP\Helper\IniLoader;
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
 * @package    Browscap
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
     * Options for the updater. The array should be overwritten,
     * containing all options as keys, set to the default value.
     *
     * @var array
     */
    private $options = array();

    /**
     * @var \BrowscapPHP\Helper\IniLoader
     */
    private $loader = null;

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
            $resourceDirectory = __DIR__.'/../resources/';

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
                .'an instanceof of \WurflCache\Adapter\AdapterInterface',
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
     * Sets multiple loader options at once
     *
     * @param array $options
     *
     * @return \BrowscapPHP\Browscap
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return \BrowscapPHP\Helper\IniLoader
     */
    public function getLoader()
    {
        if (null === $this->loader) {
            $this->loader = new IniLoader();
        }

        return $this->loader;
    }

    /**
     * @param \BrowscapPHP\Helper\IniLoader $loader
     */
    public function setLoader(IniLoader $loader)
    {
        $this->loader = $loader;
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

    /**
     * reads and parses an ini file and writes the results into the cache
     *
     * @param  string $iniFile
     *
     * @throws \BrowscapPHP\Exception
     */
    public function convertFile($iniFile)
    {
        $loader = new IniLoader();

        try {
            $loader->setLocalFile($iniFile);
        } catch (Helper\Exception $e) {
            throw new Exception('an error occured while setting the local file', 0, $e);
        }

        try {
            $iniString = $loader->load();
        } catch (Helper\Exception $e) {
            throw new Exception('an error occured while converting the local file into the cache', 0, $e);
        }

        $this->convertString($iniString);
    }

    /**
     * reads and parses an ini string and writes the results into the cache
     *
     * @param string $iniString
     */
    public function convertString($iniString)
    {
        $cachedVersion = $this->getCache()->getItem('browscap.version', false, $success);
        $converter     = new Converter($this->getLogger(), $this->getCache());

        $this->storeContent($converter, $iniString, $cachedVersion);
    }

    /**
     * fetches a remote file and stores it into a local folder
     *
     * @param string $file       The name of the file where to store the remote content
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws \BrowscapPHP\Exception\FetcherException
     * @throws \BrowscapPHP\Helper\Exception
     */
    public function fetch($file, $remoteFile = IniLoader::PHP_INI)
    {
        if (null === ($cachedVersion = $this->checkUpdate($remoteFile))) {
            // no newer version available
            return;
        }

        $this->getLoader()
            ->setRemoteFilename($remoteFile)
            ->setOptions($this->options)
            ->setLogger($this->getLogger())
        ;

        $this->getLogger()->debug('started fetching remote file');

        try {
            $content = $this->getLoader()->load();
        } catch (Helper\Exception $e) {
            throw new FetcherException('an error occured while fetching remote data', 0, $e);
        }

        if (false === $content) {
            $error = error_get_last();
            throw FetcherException::httpError($this->getLoader()->getRemoteIniUrl(), $error['message']);
        }

        $this->getLogger()->debug('finished fetching remote file');
        $this->getLogger()->debug('started storing remote file into local file');

        $content = $this->sanitizeContent($content);

        $converter  = new Converter($this->getLogger(), $this->getCache());
        $iniVersion = $converter->getIniVersion($content);

        if ($iniVersion > $cachedVersion) {
            $fs = new Filesystem();
            $fs->dumpFile($file, $content);
        }

        $this->getLogger()->debug('finished storing remote file into local file');
    }

    /**
     * fetches a remote file, parses it and writes the result into the cache
     *
     * if the local stored information are in the same version as the remote data no actions are
     * taken
     *
     * @param string      $remoteFile The code for the remote file to load
     * @param string|null $buildFolder
     * @param int|null    $buildNumber
     *
     * @throws \BrowscapPHP\Exception\FileNotFoundException
     * @throws \BrowscapPHP\Helper\Exception
     * @throws \BrowscapPHP\Exception\FetcherException
     */
    public function update($remoteFile = IniLoader::PHP_INI, $buildFolder = null, $buildNumber = null)
    {
        $this->getLogger()->debug('started fetching remote file');

        $converter = new Converter($this->getLogger(), $this->getCache());

        if (class_exists('\Browscap\Browscap')) {
            $resourceFolder = 'vendor/browscap/browscap/resources/';

            if (null === $buildNumber) {
                $buildNumber = (int)file_get_contents('vendor/browscap/browscap/BUILD_NUMBER');
            }

            if (null === $buildFolder) {
                $buildFolder = 'resources';
            }

            $buildFolder .= '/browscap-ua-test-'.$buildNumber;
            $iniFile     = $buildFolder.'/full_php_browscap.ini';

            mkdir($buildFolder, 0777, true);

            $writerCollectionFactory = new PhpWriterFactory();
            $writerCollection        = $writerCollectionFactory->createCollection($this->getLogger(), $buildFolder);

            $buildGenerator = new BuildGenerator($resourceFolder, $buildFolder);
            $buildGenerator
                ->setLogger($this->getLogger())
                ->setCollectionCreator(new CollectionCreator())
                ->setWriterCollection($writerCollection)
                ->run($buildNumber, false)
            ;

            $converter
                ->setVersion($buildNumber)
                ->storeVersion()
                ->convertFile($iniFile)
            ;

            $filesystem = new Filesystem();
            $filesystem->remove($buildFolder);
        } else {
            if (null === ($cachedVersion = $this->checkUpdate($remoteFile))) {
                // no newer version available
                return;
            }

            $this->getLoader()
                ->setRemoteFilename($remoteFile)
                ->setOptions($this->options)
                ->setLogger($this->getLogger())
            ;

            try {
                $content = $this->getLoader()->load();
            } catch (Helper\Exception $e) {
                throw new FetcherException('an error occured while loading remote data', 0, $e);
            }

            if (false === $content) {
                $internalLoader = $this->getLoader()->getLoader();
                $error          = error_get_last();

                throw FetcherException::httpError($internalLoader->getUri(), $error['message']);
            }

            $this->getLogger()->debug('finished fetching remote file');

            $this->storeContent($converter, $content, $cachedVersion);
        }
    }

    /**
     * checks if an update on a remote location for the local file or the cache
     *
     * @param string $remoteFile
     *
     * @return int|null The actual cached version if a newer version is available, null otherwise
     * @throws \BrowscapPHP\Helper\Exception
     * @throws \BrowscapPHP\Exception\FetcherException
     */
    public function checkUpdate($remoteFile = IniLoader::PHP_INI)
    {
        $success       = null;
        $cachedVersion = $this->getCache()->getItem('browscap.version', false, $success);

        if (!$cachedVersion) {
            // could not load version from cache
            $this->getLogger()->info('there is no cached version available, please update from remote');

            return 0;
        }

        $this->getLoader()
            ->setRemoteFilename($remoteFile)
            ->setOptions($this->options)
            ->setLogger($this->getLogger())
        ;

        try {
            $remoteVersion = $this->getLoader()->getRemoteVersion();
        } catch (Helper\Exception $e) {
            throw new FetcherException('an error occured while checking remote version', 0, $e);
        }

        if (!$remoteVersion) {
            // could not load remote version
            $this->getLogger()->info('could not load version from remote location');

            return 0;
        }

        if ($cachedVersion && $remoteVersion && $remoteVersion <= $cachedVersion) {
            // no newer version available
            $this->getLogger()->info('there is no newer version available');

            return null;
        }

        $this->getLogger()->info(
            'a newer version is available, local version: ' . $cachedVersion . ', remote version: ' . $remoteVersion
        );

        return (int) $cachedVersion;
    }

    /**
     * @param string $content
     *
     * @return mixed
     */
    private function sanitizeContent($content)
    {
        // replace everything between opening and closing php and asp tags
        $content = preg_replace('/<[?%].*[?%]>/', '', $content);

        // replace opening and closing php and asp tags
        return str_replace(array('<?', '<%', '?>', '%>'), '', $content);
    }

    /**
     * reads and parses an ini string and writes the results into the cache
     *
     * @param \BrowscapPHP\Helper\Converter $converter
     * @param string                        $content
     * @param int|null                      $cachedVersion
     */
    private function storeContent(Converter $converter, $content, $cachedVersion)
    {
        $iniString  = $this->sanitizeContent($content);
        $iniVersion = $converter->getIniVersion($iniString);

        if (!$cachedVersion || $iniVersion > $cachedVersion) {
            $converter
                ->storeVersion()
                ->convertString($iniString)
            ;
        }
    }
}
