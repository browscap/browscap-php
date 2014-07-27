<?php
/**
 * a outsourced cache class
 *
 * PHP version 5
 *
 * Copyright (c) 2013 Thomas Müller
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
 * @package    Browscap
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2013 Thomas Müller
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/phpbrowscap/
 */

namespace phpbrowscap\Cache;

use WurflCache\Adapter\AdapterInterface;

/**
 * Class Browscap
 *
 * @package WurflCache
 */
class BrowscapCache
{
    /**
     * Current version of the class.
     */
    const VERSION = '2.0b';

    /**
     *
     */
    const CACHE_FILE_VERSION = '2.0b';

    /**
     * The update interval in seconds.
     *
     * @var integer
     */
    const UPDATE_INTERVAL = 432000; // 5 days

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

        $this->setUpdateInterval(self::UPDATE_INTERVAL);
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

        return $data['content'];
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
            'content'      => var_export($content, true)
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
