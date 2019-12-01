<?php
declare(strict_types = 1);

namespace BrowscapPHP;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Exception\ErrorCachedVersionException;
use BrowscapPHP\Exception\ErrorReadingFileException;
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Exception\FileNameMissingException;
use BrowscapPHP\Exception\FileNotFoundException;
use BrowscapPHP\Exception\NoCachedVersionException;
use BrowscapPHP\Exception\NoNewVersionException;
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
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \GuzzleHttp\ClientInterface
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
        ?ClientInterface $client = null,
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
     * @throws \BrowscapPHP\Exception\FileNameMissingException
     * @throws \BrowscapPHP\Exception\FileNotFoundException
     * @throws \BrowscapPHP\Exception\ErrorReadingFileException
     */
    public function convertFile(string $iniFile) : void
    {
        if (empty($iniFile)) {
            throw new FileNameMissingException('the file name can not be empty');
        }

        if (! is_readable($iniFile)) {
            throw new FileNotFoundException('it was not possible to read the local file ' . $iniFile);
        }

        $iniString = file_get_contents($iniFile);

        if (false === $iniString) {
            throw new ErrorReadingFileException('an error occured while converting the local file into the cache');
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
     * @throws \BrowscapPHP\Exception\ErrorCachedVersionException
     */
    public function fetch(string $file, string $remoteFile = IniLoaderInterface::PHP_INI) : void
    {
        try {
            $cachedVersion = $this->checkUpdate();
        } catch (NoNewVersionException $e) {
            return;
        } catch (NoCachedVersionException $e) {
            $cachedVersion = 0;
        }

        $this->logger->debug('started fetching remote file');

        $loader = new IniLoader();
        $loader->setRemoteFilename($remoteFile);

        $uri = $loader->getRemoteIniUrl();

        try {
            /** @var \Psr\Http\Message\ResponseInterface $response */
            $response = $this->client->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching remote data from URI %s',
                    $uri
                ),
                0,
                $e
            );
        }

        if (200 !== $response->getStatusCode()) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching remote data from URI %s: StatusCode was %d',
                    $uri,
                    $response->getStatusCode()
                )
            );
        }

        try {
            $content = $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw new FetcherException('an error occured while fetching remote data', 0, $e);
        }

        if (empty($content)) {
            $error = error_get_last();

            if (is_array($error)) {
                throw FetcherException::httpError($uri, $error['message']);
            }

            throw FetcherException::httpError(
                $uri,
                'an error occured while fetching remote data, but no error was raised'
            );
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
     * if the local stored information are in the same version as the remote data no actions are
     * taken
     *
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws \BrowscapPHP\Exception\FetcherException
     * @throws \BrowscapPHP\Helper\Exception
     * @throws \BrowscapPHP\Exception\ErrorCachedVersionException
     */
    public function update(string $remoteFile = IniLoaderInterface::PHP_INI) : void
    {
        $this->logger->debug('started fetching remote file');

        try {
            $cachedVersion = $this->checkUpdate();
        } catch (NoNewVersionException $e) {
            return;
        } catch (NoCachedVersionException $e) {
            $cachedVersion = 0;
        }

        $loader = new IniLoader();
        $loader->setRemoteFilename($remoteFile);

        $uri = $loader->getRemoteIniUrl();

        try {
            /** @var \Psr\Http\Message\ResponseInterface $response */
            $response = $this->client->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching remote data from URI %s',
                    $uri
                ),
                0,
                $e
            );
        }

        if (200 !== $response->getStatusCode()) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching remote data from URI %s: StatusCode was %d',
                    $uri,
                    $response->getStatusCode()
                )
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
        $this->logger->debug('started updating cache from remote file');

        $converter = new Converter($this->logger, $this->cache);
        $this->storeContent($converter, $content, $cachedVersion);

        $this->logger->debug('finished updating cache from remote file');
    }

    /**
     * checks if an update on a remote location for the local file or the cache
     *
     * @throws \BrowscapPHP\Exception\FetcherException
     * @throws \BrowscapPHP\Exception\NoCachedVersionException
     * @throws \BrowscapPHP\Exception\ErrorCachedVersionException
     * @throws \BrowscapPHP\Exception\NoNewVersionException
     *
     * @return int|null The actual cached version if a newer version is available, null otherwise
     */
    public function checkUpdate() : ?int
    {
        $success = null;

        try {
            $cachedVersion = $this->cache->getItem('browscap.version', false, $success);
        } catch (InvalidArgumentException $e) {
            throw new ErrorCachedVersionException('an error occured while reading the data version from the cache', 0, $e);
        }

        if (! $cachedVersion) {
            // could not load version from cache
            throw new NoCachedVersionException('there is no cached version available, please update from remote');
        }

        $uri = (new IniLoader())->getRemoteVersionUrl();

        try {
            /** @var \Psr\Http\Message\ResponseInterface $response */
            $response = $this->client->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching version data from URI %s',
                    $uri
                ),
                0,
                $e
            );
        }

        if (200 !== $response->getStatusCode()) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching version data from URI %s: StatusCode was %d',
                    $uri,
                    $response->getStatusCode()
                )
            );
        }

        try {
            $remoteVersion = $response->getBody()->getContents();
        } catch (\Throwable $e) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching version data from URI %s: StatusCode was %d',
                    $uri,
                    $response->getStatusCode()
                ),
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
            throw new NoNewVersionException('there is no newer version available');
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
        return str_replace(['<?', '<%', '?>', '%>'], '', (string) $content);
    }

    /**
     * reads and parses an ini string and writes the results into the cache
     *
     * @param \BrowscapPHP\Helper\ConverterInterface $converter
     * @param string                                 $content
     * @param int|null                               $cachedVersion
     */
    private function storeContent(ConverterInterface $converter, string $content, ?int $cachedVersion) : void
    {
        $iniString = $this->sanitizeContent($content);
        $iniVersion = $converter->getIniVersion($iniString);

        if (! $cachedVersion || $iniVersion > $cachedVersion) {
            $converter->storeVersion();
            $converter->convertString($iniString);
        }
    }
}
