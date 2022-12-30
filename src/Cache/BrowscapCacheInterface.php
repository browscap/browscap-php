<?php

declare(strict_types=1);

namespace BrowscapPHP\Cache;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * a cache proxy to be able to use the cache adapters provided by the WurflCache package
 */
interface BrowscapCacheInterface
{
    /**
     * Gets the version of the Browscap data
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function getVersion(): ?int;

    /**
     * Gets the release date of the Browscap data
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function getReleaseDate(): ?string;

    /**
     * Gets the type of the Browscap data
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function getType(): ?string;

    /**
     * Get an item.
     *
     * @return mixed Data on success, null on failure
     *
     * @throws InvalidArgumentException
     *
     * @no-named-arguments
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function getItem(string $cacheId, bool $withVersion = true, ?bool &$success = null);

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
     * @no-named-arguments
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function setItem(string $cacheId, $content, bool $withVersion = true): bool;

    /**
     * Test if an item exists.
     *
     * @throws InvalidArgumentException
     *
     * @no-named-arguments
     */
    public function hasItem(string $cacheId, bool $withVersion = true): bool;

    /**
     * Remove an item.
     *
     * @throws InvalidArgumentException
     *
     * @no-named-arguments
     */
    public function removeItem(string $cacheId, bool $withVersion = true): bool;

    /**
     * Flush the whole storage
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function flush(): bool;
}
