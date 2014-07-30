<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Refer to the LICENSE file distributed with this package.
 *
 * @category   Browscap-PHP
 * @package    Cache
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Cache;

use WurflCache\Adapter\AdapterInterface;

/**
 * a cache proxy to be able to use the cache adapters provided by the WurflCache package
 *
 * @category   Browscap-PHP
 * @package    Cache
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class BrowscapCache
{
    /**
     * Current version of the class.
     */
    const VERSION = '2.0';

    /**
     *
     */
    const CACHE_FILE_VERSION = '2.0';

    /**
     * The cache livetime in seconds.
     *
     * @var integer
     */
    const CACHE_LIVETIME = 315360000; // ~10 years (60 * 60 * 24 * 365 * 10)

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
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param \WurflCache\Adapter\AdapterInterface $adapter
     *
     * @throws \phpbrowscap\Exception
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->cache = $adapter;

        $this->setUpdateInterval(self::CACHE_LIVETIME);
    }

    /**
     * Gets the version of the Browscap data
     *
     * @return int
     */
    public function getVersion()
    {
        if ($this->version === null) {
            $success = null;

            $version = $this->getItem('browscap.version', false, $success);

            if ($version !== null && $success) {
                $this->version = (int)$version;
            }
        }

        return $this->version;
    }

    /**
     * set the update intervall
     *
     * @param integer $updateInterval
     *
     * @return \phpbrowscap\Cache\BrowscapCache
     */
    public function setUpdateInterval($updateInterval)
    {
        $this->cache->setExpiration((int)$updateInterval);

        return $this;
    }

    /**
     * Get an item.
     *
     * @param string $cacheId
     * @param bool   $with_version
     * @param bool   $success
     *
     * @return mixed Data on success, null on failure
     */
    public function getItem($cacheId, $with_version = true, & $success = null)
    {
        $success = false;

        if ($with_version) {
            $cacheId .= '.' . $this->getVersion();
        }

        if (!$this->cache->hasItem($cacheId)) {
            return null;
        }

        $success = null;
        $data    = $this->cache->getItem($cacheId, $success);

        if (!isset($data['cacheVersion']) || $data['cacheVersion'] !== self::CACHE_FILE_VERSION) {
            return null;
        }

        return unserialize($data['content']);
    }

    /**
     * save the content into an php file
     *
     * @param string $cacheId The cache id
     * @param mixed  $content The content to store
     * @param bool   $with_version
     *
     * @return boolean whether the file was correctly written to the disk
     */
    public function setItem($cacheId, $content, $with_version = true)
    {
        // Get the whole PHP code
        $data = array(
            'cacheVersion' => self::CACHE_FILE_VERSION,
            'content'      => serialize($content)
        );

        if ($with_version) {
            $cacheId .= '.' . $this->getVersion();
        }

        // Save and return
        return $this->cache->setItem($cacheId, $data);
    }

    /**
     * Test if an item exists.
     *
     * @param string $cacheId
     * @param bool   $with_version
     *
     * @return bool
     */
    public function hasItem($cacheId, $with_version = true)
    {
        if ($with_version) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->cache->hasItem($cacheId);
    }

    /**
     * Remove an item.
     *
     * @param string $cacheId
     * @param bool   $with_version
     *
     * @return bool
     */
    public function removeItem($cacheId, $with_version = true)
    {
        if ($with_version) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->cache->removeItem($cacheId);
    }

    /**
     * Flush the whole storage
     *
     * @return bool
     */
    public function flush()
    {
        return $this->cache->flush();
    }
}
