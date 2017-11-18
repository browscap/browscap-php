<?php
declare(strict_types = 1);

namespace BrowscapPHP\Cache;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * a cache proxy to be able to use the cache adapters provided by the WurflCache package
 */
interface BrowscapCacheInterface
{
    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param \Psr\SimpleCache\CacheInterface $adapter
     * @param LoggerInterface $logger
     */
    public function __construct(CacheInterface $adapter, LoggerInterface $logger);

    /**
     * Gets the version of the Browscap data
     *
     * @return int
     */
    public function getVersion() : ?int;

    /**
     * Gets the release date of the Browscap data
     *
     * @return string|null
     */
    public function getReleaseDate() : ?string;

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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return mixed Data on success, null on failure
     */
    public function getItem(string $cacheId, bool $withVersion = true, ?bool &$success = null);

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
    public function setItem(string $cacheId, $content, bool $withVersion = true) : bool;

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
    public function hasItem(string $cacheId, bool $withVersion = true) : bool;

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
    public function removeItem(string $cacheId, bool $withVersion = true) : bool;

    /**
     * Flush the whole storage
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return bool
     */
    public function flush() : bool;
}
