<?php
declare(strict_types=1);

namespace BrowscapPHP\Cache;

use WurflCache\Adapter\AdapterInterface;

/**
 * A cache proxy to be able to use the cache adapters provided by the WurflCache package
 */
final class BrowscapCache implements BrowscapCacheInterface
{
    /**
     * Path to the cache directory
     *
     * @var \WurflCache\Adapter\AdapterInterface
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
     * @param \WurflCache\Adapter\AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
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
            $success = true;

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
     * @return string
     */
    public function getReleaseDate() : string
    {
        if ($this->releaseDate === null) {
            $success = true;

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
            $success = true;

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
     * @param bool   $success
     *
     * @return mixed Data on success, null on failure
     */
    public function getItem(string $cacheId, bool $withVersion = true, ?bool & $success = null)
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        if (!$this->cache->hasItem($cacheId)) {
            $success = false;

            return null;
        }

        $success = null;
        $data    = $this->cache->getItem($cacheId, $success);

        if (!$success) {
            $success = false;

            return null;
        }

        if (!isset($data['content'])) {
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
        return $this->cache->setItem($cacheId, $data);
    }

    /**
     * Test if an item exists.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @return bool
     */
    public function hasItem(string $cacheId, bool $withVersion = true) : bool
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->cache->hasItem($cacheId);
    }

    /**
     * Remove an item.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @return bool
     */
    public function removeItem(string $cacheId, bool $withVersion = true) : bool
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->cache->removeItem($cacheId);
    }

    /**
     * Flush the whole storage
     *
     * @return bool
     */
    public function flush() : bool
    {
        return $this->cache->flush();
    }
}
