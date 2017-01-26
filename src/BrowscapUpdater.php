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
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Helper\Converter;
use BrowscapPHP\Helper\Filesystem;
use BrowscapPHP\Helper\IniLoader;
use GuzzleHttp\Client;
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
class BrowscapUpdater
{
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
     * @var \GuzzleHttp\Client
     */
    private $client = null;

    /**
     * Curl connect timeout in seconds
     *
     * @var int
     */
    private $connectTimeout = 5;

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
     *
     * @return self
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
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return self
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
     * Sets the Connection Timeout
     *
     * @param int $connectTimeout
     */
    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = (int) $connectTimeout;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        if (null === $this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * @param \GuzzleHttp\Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * reads and parses an ini file and writes the results into the cache
     *
     * @param string $iniFile
     *
     * @throws \BrowscapPHP\Exception
     */
    public function convertFile($iniFile)
    {
        if (empty($iniFile)) {
            throw new Exception('the file name can not be empty');
        }

        if (!is_readable($iniFile)) {
            throw new Exception('it was not possible to read the local file ' . $iniFile);
        }

        try {
            $iniString = file_get_contents($iniFile);
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
        if (null === ($cachedVersion = $this->checkUpdate())) {
            // no newer version available
            return;
        }

        $this->getLogger()->debug('started fetching remote file');

        $uri = (new IniLoader())->setRemoteFilename($remoteFile)->getRemoteIniUrl();

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->getClient()->get($uri, ['connect_timeout' => $this->connectTimeout]);

        if ($response->getStatusCode() !== 200) {
            throw new FetcherException(
                'an error occured while fetching remote data from URI ' . $uri . ': StatusCode was '
                . $response->getStatusCode()
            );
        }

        try {
            $content = $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw new FetcherException('an error occured while fetching remote data', 0, $e);
        }

        if (empty($content)) {
            $error = error_get_last();
            throw FetcherException::httpError($uri, $error['message']);
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
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws \BrowscapPHP\Exception\FileNotFoundException
     * @throws \BrowscapPHP\Helper\Exception
     * @throws \BrowscapPHP\Exception\FetcherException
     */
    public function update($remoteFile = IniLoader::PHP_INI)
    {
        $this->getLogger()->debug('started fetching remote file');

        if (null === ($cachedVersion = $this->checkUpdate())) {
            // no newer version available
            return;
        }

        $uri = (new IniLoader())->setRemoteFilename($remoteFile)->getRemoteIniUrl();

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->getClient()->get($uri, ['connect_timeout' => $this->connectTimeout]);

        if ($response->getStatusCode() !== 200) {
            throw new FetcherException(
                'an error occured while fetching remote data from URI ' . $uri . ': StatusCode was '
                . $response->getStatusCode()
            );
        }

        try {
            $content = $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw new FetcherException('an error occured while fetching remote data', 0, $e);
        }

        if (empty($content)) {
            $error = error_get_last();

            throw FetcherException::httpError($uri, $error['message']);
        }

        $this->getLogger()->debug('finished fetching remote file');

        $converter = new Converter($this->getLogger(), $this->getCache());

        $this->storeContent($converter, $content, $cachedVersion);
    }

    /**
     * checks if an update on a remote location for the local file or the cache
     *
     * @throws \BrowscapPHP\Helper\Exception
     * @throws \BrowscapPHP\Exception\FetcherException
     * @return int|null                                The actual cached version if a newer version is available, null otherwise
     * @return int|null                                The actual cached version if a newer version is available, null otherwise
     */
    public function checkUpdate()
    {
        $success       = null;
        $cachedVersion = $this->getCache()->getItem('browscap.version', false, $success);

        if (!$cachedVersion) {
            // could not load version from cache
            $this->getLogger()->info('there is no cached version available, please update from remote');

            return 0;
        }

        $uri = (new IniLoader())->getRemoteVersionUrl();

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->getClient()->get($uri, ['connect_timeout' => $this->connectTimeout]);

        if ($response->getStatusCode() !== 200) {
            throw new FetcherException(
                'an error occured while fetching version data from URI ' . $uri . ': StatusCode was '
                . $response->getStatusCode()
            );
        }

        try {
            $remoteVersion = $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw new FetcherException(
                'an error occured while fetching version data from URI ' . $uri . ': StatusCode was '
                . $response->getStatusCode(),
                0,
                $e
            );
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
        return str_replace(['<?', '<%', '?>', '%>'], '', $content);
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
                ->convertString($iniString);
        }
    }
}
