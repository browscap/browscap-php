<?php
declare(strict_types = 1);

namespace BrowscapPHP;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Exception\NoCachedVersionException;
use BrowscapPHP\Helper\Converter;
use BrowscapPHP\Helper\ConverterInterface;
use BrowscapPHP\Helper\Filesystem;
use BrowscapPHP\Helper\IniLoader;
use BrowscapPHP\Helper\IniLoaderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Browscap.ini parsing class with caching and update capabilities
 */
final class BrowscapUpdater implements BrowscapUpdaterInterface
{
    public const DEFAULT_TIMEOUT = 5;

    /**
     * The cache instance
     *
     * @var \BrowscapPHP\Cache\BrowscapCacheInterface
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
    private $connectTimeout;

    /**
     * Browscap constructor.
     *
     * @param \Psr\SimpleCache\CacheInterface $cache
     * @param LoggerInterface                 $logger
     * @param ClientInterface|null            $client
     * @param int                             $connectTimeout
     */
    public function __construct(
        CacheInterface $cache,
        LoggerInterface $logger,
        ClientInterface $client = null,
        int $connectTimeout = self::DEFAULT_TIMEOUT
    ) {
        $this->cache = new BrowscapCache($cache, $logger);
        $this->logger = $logger;

        if (null === $client) {
            $client = new Client();
        }

        $this->client = $client;
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * reads and parses an ini file and writes the results into the cache
     *
     * @param string $iniFile
     *
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
        try {
            $cachedVersion = $this->cache->getItem('browscap.version', false, $success);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(new \InvalidArgumentException('an error occured while reading the data version from the cache', 0, $e));

            return;
        }

        $converter = new Converter($this->logger, $this->cache);

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
    public function fetch(string $file, string $remoteFile = IniLoaderInterface::PHP_INI) : void
    {
        try {
            if (null === ($cachedVersion = $this->checkUpdate())) {
                // no newer version available
                return;
            }
        } catch (NoCachedVersionException $e) {
            $cachedVersion = 0;
        }

        $this->logger->debug('started fetching remote file');

        $loader = new IniLoader();
        $loader->setRemoteFilename($remoteFile);

        $uri = $loader->getRemoteIniUrl();

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->client->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);

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

        $this->logger->debug('finished fetching remote file');
        $this->logger->debug('started storing remote file into local file');

        $content = $this->sanitizeContent($content);

        $converter = new Converter($this->logger, $this->cache);
        $iniVersion = $converter->getIniVersion($content);

        if ($iniVersion > $cachedVersion) {
            $fs = new Filesystem();
            $fs->dumpFile($file, $content);
        }

        $this->logger->debug('finished storing remote file into local file');
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
    public function update(string $remoteFile = IniLoaderInterface::PHP_INI) : void
    {
        $this->logger->debug('started fetching remote file');

        try {
            if (null === ($cachedVersion = $this->checkUpdate())) {
                // no newer version available
                return;
            }
        } catch (NoCachedVersionException $e) {
            $cachedVersion = 0;
        }

        $loader = new IniLoader();
        $loader->setRemoteFilename($remoteFile);

        $uri = $loader->getRemoteIniUrl();

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->client->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);

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

        $this->logger->debug('finished fetching remote file');

        $converter = new Converter($this->logger, $this->cache);

        $this->storeContent($converter, $content, $cachedVersion);
    }

    /**
     * checks if an update on a remote location for the local file or the cache
     *
     * @throws \BrowscapPHP\Helper\Exception
     * @throws \BrowscapPHP\Exception\FetcherException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \BrowscapPHP\Exception\NoCachedVersionException
     *
     * @return int|null The actual cached version if a newer version is available, null otherwise
     */
    public function checkUpdate() : ?int
    {
        $success = null;
        try {
            $cachedVersion = $this->cache->getItem('browscap.version', false, $success);
        } catch (InvalidArgumentException $e) {
            throw new NoCachedVersionException('an error occured while reading the data version from the cache', 0, $e);
        }

        if (! $cachedVersion) {
            // could not load version from cache
            throw new NoCachedVersionException('there is no cached version available, please update from remote');
        }

        $uri = (new IniLoader())->getRemoteVersionUrl();

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = $this->client->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);

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
            $this->logger->info('there is no newer version available');

            return null;
        }

        $this->logger->info(
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
     * @param \BrowscapPHP\Helper\ConverterInterface $converter
     * @param string                                 $content
     * @param int|null                               $cachedVersion
     */
    private function storeContent(ConverterInterface $converter, string $content, ?int $cachedVersion)
    {
        $iniString = $this->sanitizeContent($content);
        $iniVersion = $converter->getIniVersion($iniString);

        if (! $cachedVersion || $iniVersion > $cachedVersion) {
            $converter->storeVersion();
            $converter->convertString($iniString);
        }
    }
}
