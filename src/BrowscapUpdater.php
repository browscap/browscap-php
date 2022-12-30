<?php

declare(strict_types=1);

namespace BrowscapPHP;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception\ErrorCachedVersionException;
use BrowscapPHP\Exception\ErrorReadingFileException;
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Exception\FileNameMissingException;
use BrowscapPHP\Exception\FileNotFoundException;
use BrowscapPHP\Exception\NoCachedVersionException;
use BrowscapPHP\Exception\NoNewVersionException;
use BrowscapPHP\Helper\Converter;
use BrowscapPHP\Helper\ConverterInterface;
use BrowscapPHP\Helper\Exception;
use BrowscapPHP\Helper\Filesystem;
use BrowscapPHP\Helper\IniLoader;
use BrowscapPHP\Helper\IniLoaderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Filesystem\Exception\IOException;
use Throwable;

use function assert;
use function error_get_last;
use function file_get_contents;
use function is_array;
use function is_int;
use function is_readable;
use function preg_replace;
use function sprintf;
use function str_replace;

/**
 * Browscap.ini parsing class with caching and update capabilities
 */
final class BrowscapUpdater implements BrowscapUpdaterInterface
{
    public const DEFAULT_TIMEOUT = 5;

    /**
     * The cache instance
     */
    private BrowscapCacheInterface $cache;

    private LoggerInterface $logger;

    private ClientInterface $client;

    /**
     * Curl connect timeout in seconds
     */
    private int $connectTimeout;

    /** @throws void */
    public function __construct(
        CacheInterface $cache,
        LoggerInterface $logger,
        ?ClientInterface $client = null,
        int $connectTimeout = self::DEFAULT_TIMEOUT
    ) {
        $this->cache  = new BrowscapCache($cache, $logger);
        $this->logger = $logger;

        if ($client === null) {
            $client = new Client();
        }

        $this->client         = $client;
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * reads and parses an ini file and writes the results into the cache
     *
     * @throws FileNameMissingException
     * @throws FileNotFoundException
     * @throws ErrorReadingFileException
     */
    public function convertFile(string $iniFile): void
    {
        if (empty($iniFile)) {
            throw new FileNameMissingException('the file name can not be empty');
        }

        if (! is_readable($iniFile)) {
            throw new FileNotFoundException(
                sprintf('it was not possible to read the local file %s', $iniFile),
            );
        }

        $iniString = file_get_contents($iniFile);

        if ($iniString === false) {
            throw new ErrorReadingFileException('an error occured while converting the local file into the cache');
        }

        $this->convertString($iniString);
    }

    /**
     * reads and parses an ini string and writes the results into the cache
     *
     * @throws void
     */
    public function convertString(string $iniString): void
    {
        try {
            $cachedVersion = $this->cache->getItem('browscap.version', false, $success);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(new \InvalidArgumentException('an error occured while reading the data version from the cache', 0, $e));

            return;
        }

        assert($cachedVersion === null || is_int($cachedVersion));

        $converter = new Converter($this->logger, $this->cache);

        $this->storeContent($converter, $iniString, $cachedVersion);
    }

    /**
     * fetches a remote file and stores it into a local folder
     *
     * @param string $file       The name of the file where to store the remote content
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws FetcherException
     * @throws Exception
     * @throws ErrorCachedVersionException
     */
    public function fetch(string $file, string $remoteFile = IniLoaderInterface::PHP_INI): void
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
            $response = $this->client->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);
            assert($response instanceof ResponseInterface);
        } catch (GuzzleException $e) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching remote data from URI %s',
                    $uri,
                ),
                0,
                $e,
            );
        }

        if ($response->getStatusCode() !== 200) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching remote data from URI %s: StatusCode was %d',
                    $uri,
                    $response->getStatusCode(),
                ),
            );
        }

        try {
            $content = $response->getBody()->getContents();
        } catch (Throwable $e) {
            throw new FetcherException('an error occured while fetching remote data', 0, $e);
        }

        if (empty($content)) {
            $error = error_get_last();

            if (is_array($error)) {
                throw FetcherException::httpError($uri, $error['message']);
            }

            throw FetcherException::httpError(
                $uri,
                'an error occured while fetching remote data, but no error was raised',
            );
        }

        $this->logger->debug('finished fetching remote file');
        $this->logger->debug('started storing remote file into local file');

        $content = $this->sanitizeContent($content);

        $converter  = new Converter($this->logger, $this->cache);
        $iniVersion = $converter->getIniVersion($content);

        if ($iniVersion > $cachedVersion) {
            $fs = new Filesystem();

            try {
                $fs->dumpFile($file, $content);
            } catch (IOException $exception) {
                throw new FetcherException('an error occured while writing fetched data to local file', 0, $exception);
            }
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
     * @throws FetcherException
     * @throws Exception
     * @throws ErrorCachedVersionException
     */
    public function update(string $remoteFile = IniLoaderInterface::PHP_INI): void
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
            $response = $this->client->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);
            assert($response instanceof ResponseInterface);
        } catch (GuzzleException $e) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching remote data from URI %s',
                    $uri,
                ),
                0,
                $e,
            );
        }

        if ($response->getStatusCode() !== 200) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching remote data from URI %s: StatusCode was %d',
                    $uri,
                    $response->getStatusCode(),
                ),
            );
        }

        try {
            $content = $response->getBody()->getContents();
        } catch (Throwable $e) {
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
     * @return int|null The actual cached version if a newer version is available, null otherwise
     *
     * @throws FetcherException
     * @throws NoCachedVersionException
     * @throws ErrorCachedVersionException
     * @throws NoNewVersionException
     */
    public function checkUpdate(): ?int
    {
        $success = null;

        try {
            $cachedVersion = $this->cache->getItem('browscap.version', false, $success);
        } catch (InvalidArgumentException $e) {
            throw new ErrorCachedVersionException('an error occured while reading the data version from the cache', 0, $e);
        }

        assert($cachedVersion === null || is_int($cachedVersion));

        if (! $cachedVersion) {
            // could not load version from cache
            throw new NoCachedVersionException('there is no cached version available, please update from remote');
        }

        $uri = (new IniLoader())->getRemoteVersionUrl();

        try {
            $response = $this->client->request('get', $uri, ['connect_timeout' => $this->connectTimeout]);
            assert($response instanceof ResponseInterface);
        } catch (GuzzleException $e) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching version data from URI %s',
                    $uri,
                ),
                0,
                $e,
            );
        }

        if ($response->getStatusCode() !== 200) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching version data from URI %s: StatusCode was %d',
                    $uri,
                    $response->getStatusCode(),
                ),
            );
        }

        try {
            $remoteVersion = $response->getBody()->getContents();
        } catch (Throwable $e) {
            throw new FetcherException(
                sprintf(
                    'an error occured while fetching version data from URI %s: StatusCode was %d',
                    $uri,
                    $response->getStatusCode(),
                ),
                0,
                $e,
            );
        }

        if (! $remoteVersion) {
            // could not load remote version
            throw new FetcherException(
                'could not load version from remote location',
            );
        }

        if ($remoteVersion <= $cachedVersion) {
            throw new NoNewVersionException('there is no newer version available');
        }

        $this->logger->info(
            sprintf(
                'a newer version is available, local version: %s, remote version: %s',
                $cachedVersion,
                $remoteVersion,
            ),
        );

        return (int) $cachedVersion;
    }

    /** @throws void */
    private function sanitizeContent(string $content): string
    {
        // replace everything between opening and closing php and asp tags
        $content = preg_replace('/<[?%].*[?%]>/', '', $content);

        // replace opening and closing php and asp tags
        return str_replace(['<?', '<%', '?>', '%>'], '', (string) $content);
    }

    /**
     * reads and parses an ini string and writes the results into the cache
     *
     * @throws void
     */
    private function storeContent(ConverterInterface $converter, string $content, ?int $cachedVersion): void
    {
        $iniString  = $this->sanitizeContent($content);
        $iniVersion = $converter->getIniVersion($iniString);

        if ($cachedVersion && $iniVersion <= $cachedVersion) {
            return;
        }

        $converter->storeVersion();
        $converter->convertString($iniString);
    }
}
