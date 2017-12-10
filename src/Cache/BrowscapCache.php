<?php
declare(strict_types = 1);

namespace BrowscapPHP\Cache;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * A cache proxy to be able to use the cache adapters provided by the WurflCache package
 */
final class BrowscapCache implements BrowscapCacheInterface
{
    /**
     * Path to the cache directory
     *
     * @var \Psr\SimpleCache\CacheInterface
     */
    private $cache;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Detected browscap version (read from INI file)
     *
     * @var int
     */
    private $version;

    /**
     * Release date of the Browscap data (read from INI file)
     *
     * @var string
     */
    private $releaseDate;

    /**
     * Type of the Browscap data (read from INI file)
     *
     * @var string
     */
    private $type;

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param \Psr\SimpleCache\CacheInterface $adapter
     * @param LoggerInterface $logger
     */
    public function __construct(CacheInterface $adapter, LoggerInterface $logger)
    {
        $this->cache = $adapter;
        $this->logger = $logger;
    }

    /**
     * Gets the version of the Browscap data
     *
     * @return int
     */
    public function getVersion() : ?int
    {
        if (null === $this->version) {
            $success = null;

            try {
                $version = $this->getItem('browscap.version', false, $success);
            } catch (InvalidArgumentException $e) {
                $this->logger->error(new \InvalidArgumentException('an error occured while reading the data version from the cache', 0, $e));
                $version = null;
            }

            if (null !== $version && $success) {
                $this->version = (int) $version;
            }
        }

        return $this->version;
    }

    /**
     * Gets the release date of the Browscap data
     *
     * @return string|null
     */
    public function getReleaseDate() : ?string
    {
        if (null === $this->releaseDate) {
            $success = null;

            try {
                $releaseDate = $this->getItem('browscap.releaseDate', false, $success);
            } catch (InvalidArgumentException $e) {
                $this->logger->error(new \InvalidArgumentException('an error occured while reading the data release date from the cache', 0, $e));
                $releaseDate = null;
            }

            if (null !== $releaseDate && $success) {
                $this->releaseDate = $releaseDate;
            }
        }

        return $this->releaseDate;
    }

    /**
     * Gets the type of the Browscap data
     */
    public function getType() : ?string
    {
        if (null === $this->type) {
            $success = null;

            try {
                $type = $this->getItem('browscap.type', false, $success);
            } catch (InvalidArgumentException $e) {
                $this->logger->error(new \InvalidArgumentException('an error occured while reading the data type from the cache', 0, $e));
                $type = null;
            }

            if (null !== $type && $success) {
                $this->type = $type;
            }
        }

        return $this->type;
    }

    /**
     * Get an item.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     * @param bool   $success
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return mixed Data on success, null on failure
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
     * @param string $cacheId     The cache id
     * @param mixed  $content     The content to store
     * @param bool   $withVersion
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return bool whether the file was correctly written to the disk
     */
    public function setItem(string $cacheId, $content, bool $withVersion = true) : bool
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
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return bool
     */
    public function hasItem(string $cacheId, bool $withVersion = true) : bool
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->cache->has($cacheId);
    }

    /**
     * Remove an item.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return bool
     */
    public function removeItem(string $cacheId, bool $withVersion = true) : bool
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->cache->delete($cacheId);
    }

    /**
     * Flush the whole storage
     *
     * @return bool
     */
    public function flush() : bool
    {
        return $this->cache->clear();
    }
}
