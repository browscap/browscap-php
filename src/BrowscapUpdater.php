<?php
declare(strict_types = 1);

namespace BrowscapPHP;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Exception\NoCachedVersionException;
use BrowscapPHP\Helper\Converter;
use BrowscapPHP\Helper\Filesystem;
use BrowscapPHP\Helper\IniLoader;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WurflCache\Adapter\AdapterInterface;
use WurflCache\Adapter\File;

/**
 * Browscap.ini parsing class with caching and update capabilities
 */
final class BrowscapUpdater
{
    /**
     * The cache instance
     *
     * @var \BrowscapPHP\Cache\BrowscapCacheInterface|null
     */
    private $cache;

    /**
     * @var @var \Psr\Log\LoggerInterface|null
     */
    private $logger;

    /**
     * @var \GuzzleHttp\ClientInterface|null
     */
    private $client;

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
    public function getCache() : BrowscapCacheInterface
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
     * @throws \BrowscapPHP\Exception
     * @return self
     */
    public function setCache($cache) : self
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
    public function setLogger(LoggerInterface $logger) : self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * returns a logger instance
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger() : LoggerInterface
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
    public function setConnectTimeout(int $connectTimeout) : void
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function getClient() : ClientInterface
    {
        if (null === $this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * reads and parses an ini file and writes the results into the cache
     *
     * @param string $iniFile
     * @throws \BrowscapPHP\Exception
     */
    public function convertFile(string $iniFile) : void
    {
        if (empty($iniFile)) {
            throw new Exception('the file name can not be empty');
        }

        if (! is_readable($iniFile)) {
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
    public function convertString(string $iniString) : void
    {
        $cachedVersion = $this->getCache()->getItem('browscap.version', false, $success);
        $converter = new Converter($this->getLogger(), $this->getCache());

        $this->storeContent($converter, $iniString, $cachedVersion);
    }

    /**
     * fetches a remote file and stores it into a local folder
     *
     * @param string $file The name of the file where to store the remote content
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws \BrowscapPHP\Exception\FetcherException
     * @throws \BrowscapPHP\Helper\Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch(string $file, string $remoteFile = IniLoader::PHP_INI) : void
    {
        try {
            if (null === ($cachedVersion = $this->checkUpdate())) {
                // no newer version available
                return;
            }
        } catch (NoCachedVersionException $e) {
            $cachedVersion = 0;
        }

        $this->getLogger()->debug('started fetching remote file');

        $uri = (new IniLoader())->setRemoteFilename($remoteFile)->getRemoteIniUrl();

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->getClient()->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);

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

        $converter = new Converter($this->getLogger(), $this->getCache());
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function update(string $remoteFile = IniLoader::PHP_INI) : void
    {
        $this->getLogger()->debug('started fetching remote file');

        try {
            if (null === ($cachedVersion = $this->checkUpdate())) {
                // no newer version available
                return;
            }
        } catch (NoCachedVersionException $e) {
            $cachedVersion = 0;
        }

        $uri = (new IniLoader())->setRemoteFilename($remoteFile)->getRemoteIniUrl();

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->getClient()->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);

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

            throw FetcherException::httpError($uri, $error['message'] ?? '');
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
     * @return int|null The actual cached version if a newer version is available, null otherwise
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \BrowscapPHP\Exception\NoCachedVersionException
     */
    public function checkUpdate() : ?int
    {
        $success = null;
        $cachedVersion = $this->getCache()->getItem('browscap.version', false, $success);

        if (! $cachedVersion) {
            // could not load version from cache
            throw new NoCachedVersionException('there is no cached version available, please update from remote');
        }

        $uri = (new IniLoader())->getRemoteVersionUrl();

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->getClient()->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);

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

        if (! $remoteVersion) {
            // could not load remote version
            throw new FetcherException(
                'could not load version from remote location'
            );
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

    private function sanitizeContent(string $content) : string
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
    private function storeContent(Converter $converter, string $content, ?int $cachedVersion)
    {
        $iniString = $this->sanitizeContent($content);
        $iniVersion = $converter->getIniVersion($iniString);

        if (! $cachedVersion || $iniVersion > $cachedVersion) {
            $converter
                ->storeVersion()
                ->convertString($iniString);
        }
    }
}
