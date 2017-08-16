<?php
declare(strict_types = 1);

namespace BrowscapPHP\Cache;

use Psr\SimpleCache\CacheInterface;
use WurflCache\Adapter\AdapterInterface;

/**
 * a cache proxy to be able to use the cache adapters provided by the WurflCache package
 */
interface BrowscapCacheInterface
{
    /**
     * The cache livetime in seconds.
     *
     * @var int
     */
    const CACHE_LIVETIME = 315360000; // ~10 years (60 * 60 * 24 * 365 * 10)

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param \Psr\SimpleCache\CacheInterface $adapter
     */
    public function __construct(CacheInterface $adapter);

    /**
     * Gets the version of the Browscap data
     *
     * @return int
     */
    public function getVersion() : ?int;

    /**
     * Gets the release date of the Browscap data
     *
     * @return string
     */
    public function getReleaseDate() : string;

    /**
     * Gets the type of the Browscap data
     *
     * @return string
     */
    public function getType() : ?string;

    /**
     * Get an item.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     * @param bool   $success
     *
     * @return mixed Data on success, null on failure
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getItem(string $cacheId, bool $withVersion = true, ?bool &$success = null);

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
    public function setItem(string $cacheId, $content, bool $withVersion = true) : bool;

    /**
     * Test if an item exists.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function hasItem(string $cacheId, bool $withVersion = true) : bool;

    /**
     * Remove an item.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function removeItem(string $cacheId, bool $withVersion = true) : bool;

    /**
     * Flush the whole storage
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function flush() : bool;
}
