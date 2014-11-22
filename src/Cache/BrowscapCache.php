<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   Browscap-PHP
 * @package    Cache
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace BrowscapPHP\Cache;

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
     * @throws \BrowscapPHP\Exception
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this
            ->setCacheAdapter($adapter)
            ->setUpdateInterval(self::CACHE_LIVETIME)
        ;
    }

    /**
     * sets the cache adapter
     *
     * @param \WurflCache\Adapter\AdapterInterface $adapter
     *
     * @return \BrowscapPHP\Cache\BrowscapCache
     */
    public function setCacheAdapter(AdapterInterface $adapter)
    {
        $this->cache = $adapter;

        return $this;
    }

    /**
     * returns the cache adapter
     *
     * @return \WurflCache\Adapter\AdapterInterface
     */
    public function getCacheAdapter()
    {
        return $this->cache;
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
     * @return \BrowscapPHP\Cache\BrowscapCache
     */
    public function setUpdateInterval($updateInterval)
    {
        $this->getCacheAdapter()->setExpiration((int)$updateInterval);

        return $this;
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
    public function getItem($cacheId, $withVersion = true, & $success = null)
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        if (!$this->getCacheAdapter()->hasItem($cacheId)) {
            $success = false;

            return null;
        }

        $success = null;
        $data    = $this->getCacheAdapter()->getItem($cacheId, $success);

        if (!isset($data['cacheVersion']) || $data['cacheVersion'] !== self::CACHE_FILE_VERSION) {
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
     * @param bool   $withVersion
     *
     * @return boolean whether the file was correctly written to the disk
     */
    public function setItem($cacheId, $content, $withVersion = true)
    {
        // Get the whole PHP code
        $data = array(
            'cacheVersion' => self::CACHE_FILE_VERSION,
            'content'      => serialize($content)
        );

        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        // Save and return
        return $this->getCacheAdapter()->setItem($cacheId, $data);
    }

    /**
     * Test if an item exists.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @return bool
     */
    public function hasItem($cacheId, $withVersion = true)
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->getCacheAdapter()->hasItem($cacheId);
    }

    /**
     * Remove an item.
     *
     * @param string $cacheId
     * @param bool   $withVersion
     *
     * @return bool
     */
    public function removeItem($cacheId, $withVersion = true)
    {
        if ($withVersion) {
            $cacheId .= '.' . $this->getVersion();
        }

        return $this->getCacheAdapter()->removeItem($cacheId);
    }

    /**
     * Flush the whole storage
     *
     * @return bool
     */
    public function flush()
    {
        return $this->getCacheAdapter()->flush();
    }
}
