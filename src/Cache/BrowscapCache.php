<?php

declare(strict_types=1);

namespace BrowscapPHP\Cache;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

use function array_key_exists;
use function assert;
use function is_array;
use function is_int;
use function is_string;
use function serialize;
use function unserialize;

/**
 * A cache proxy to be able to use the cache adapters provided by the WurflCache package
 */
final class BrowscapCache implements BrowscapCacheInterface
{
    /**
     * Path to the cache directory
     */
    private CacheInterface $cache;

    private LoggerInterface $logger;

    /**
     * Detected browscap version (read from INI file)
     */
    private ?int $version = null;

    /**
     * Release date of the Browscap data (read from INI file)
     */
    private ?string $releaseDate = null;

    /**
     * Type of the Browscap data (read from INI file)
     */
    private ?string $type = null;

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @throws void
     */
    public function __construct(CacheInterface $adapter, LoggerInterface $logger)
    {
        $this->cache  = $adapter;
        $this->logger = $logger;
    }

    /**
     * Gets the version of the Browscap data
     *
     * @throws void
     */
    public function getVersion(): ?int
    {
        if ($this->version === null) {
            $success = null;

            try {
                $cachedVersion = $this->getItem('browscap.version', false, $success);
            } catch (InvalidArgumentException $e) {
                $this->logger->error(new \InvalidArgumentException('an error occured while reading the data version from the cache', 0, $e));
                $cachedVersion = null;
            }

            assert($cachedVersion === null || is_int($cachedVersion));

            if ($cachedVersion !== null && $success) {
                $this->version = (int) $cachedVersion;
            }
        }

        return $this->version;
    }

    /**
     * Gets the release date of the Browscap data
     *
     * @throws void
     */
    public function getReleaseDate(): ?string
    {
        if ($this->releaseDate === null) {
            $success = null;

            try {
                $releaseDate = $this->getItem('browscap.releaseDate', false, $success);
            } catch (InvalidArgumentException $e) {
                $this->logger->error(new \InvalidArgumentException('an error occured while reading the data release date from the cache', 0, $e));
                $releaseDate = null;
            }

            assert($releaseDate === null || is_string($releaseDate));

            if ($releaseDate !== null && $success) {
                $this->releaseDate = $releaseDate;
            }
        }

        return $this->releaseDate;
    }

    /**
     * Gets the type of the Browscap data
     *
     * @throws void
     */
    public function getType(): ?string
    {
        if ($this->type === null) {
            $success = null;

            try {
                $type = $this->getItem('browscap.type', false, $success);
            } catch (InvalidArgumentException $e) {
                $this->logger->error(new \InvalidArgumentException('an error occured while reading the data type from the cache', 0, $e));
                $type = null;
            }

            assert($type === null || is_string($type));

            if ($type !== null && $success) {
                $this->type = $type;
            }
        }

        return $this->type;
    }

    /**
     * Get an item.
     *
     * @return mixed Data on success, null on failure
     *
     * @throws InvalidArgumentException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function getItem(string $cacheId, bool $withVersion = true, ?bool &$success = null)
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        if (! $this->cache->has($cacheId)) {
            $success = false;

            return null;
        }

        $data = $this->cache->get($cacheId);

        if (! is_array($data) || ! array_key_exists('content', $data)) {
            $success = false;

            return null;
        }

        $success = true;

        return unserialize($data['content']);
    }

    /**
     * save the content into an php file
     *
     * @param string $cacheId The cache id
     * @param mixed  $content The content to store
     *
     * @return bool whether the file was correctly written to the disk
     *
     * @throws InvalidArgumentException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function setItem(string $cacheId, $content, bool $withVersion = true): bool
    {
        // Get the whole PHP code
        $data = [
            'content' => serialize($content),
        ];

        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        // Save and return
        return $this->cache->set($cacheId, $data);
    }

    /**
     * Test if an item exists.
     *
     * @throws InvalidArgumentException
     */
    public function hasItem(string $cacheId, bool $withVersion = true): bool
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->cache->has($cacheId);
    }

    /**
     * Remove an item.
     *
     * @throws InvalidArgumentException
     */
    public function removeItem(string $cacheId, bool $withVersion = true): bool
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->cache->delete($cacheId);
    }

    /**
     * Flush the whole storage
     *
     * @throws void
     */
    public function flush(): bool
    {
        return $this->cache->clear();
    }
}
