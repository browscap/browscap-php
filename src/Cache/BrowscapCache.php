<?php
declare(strict_types = 1);

namespace BrowscapPHP\Cache;

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
    private $cache = null;

    /**
     * Detected browscap version (read from INI file)
     *
     * @var int
     */
    private $version = null;

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
     */
    public function __construct(CacheInterface $adapter)
    {
        $this->cache = $adapter;
    }

    /**
     * Gets the version of the Browscap data
     *
     * @return int
     */
    public function getVersion() : ?int
    {
        if ($this->version === null) {
            $success = null;

            $version = $this->getItem('browscap.version', false, $success);

            if ($version !== null && $success) {
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
        if ($this->releaseDate === null) {
            $success = null;

            $releaseDate = $this->getItem('browscap.releaseDate', false, $success);

            if ($releaseDate !== null && $success) {
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
        if ($this->type === null) {
            $success = null;

            $type = $this->getItem('browscap.type', false, $success);

            if ($type !== null && $success) {
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
     * @param bool   &$success
     *
     * @return mixed Data on success, null on failure
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getItem(string $cacheId, bool $withVersion = true, ?bool &$success = null)
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        try {
            if (! $this->cache->has($cacheId)) {
                $success = false;

                return null;
            }
        } catch (InvalidArgumentException $e) {
            $success = false;

            return null;
        }

        try {
            $data = $this->cache->get($cacheId);
        } catch (InvalidArgumentException $e) {
            $success = false;

            return null;
        }

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
     * @return bool whether the file was correctly written to the disk
     * @throws \Psr\SimpleCache\InvalidArgumentException
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

        try {
            // Save and return
            return $this->cache->set($cacheId, $data);
        } catch (InvalidArgumentException $e) {
            // do nothing here
        }

        return false;
    }

    /**
     * Test if an item exists.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function hasItem(string $cacheId, bool $withVersion = true) : bool
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }


        try {
            return $this->cache->has($cacheId);
        } catch (InvalidArgumentException $e) {
            // do nothing here
        }

        return false;
    }

    /**
     * Remove an item.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function removeItem(string $cacheId, bool $withVersion = true) : bool
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        try {
            return $this->cache->delete($cacheId);
        } catch (InvalidArgumentException $e) {
            // do nothing here
        }

        return false;
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
