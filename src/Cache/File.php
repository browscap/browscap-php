<?php
namespace phpbrowscap\Cache;

/**
 * File cache class
 *
 * The file cache is the basic cache adapter that is used by default, because
 * it's always available.
 *
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Christoph Ziegenberg <christoph@ziegenberg.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @package phpbrowscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @copyright Copyright (c) 2014 Christoph Ziegenberg <christoph@ziegenberg.com>
 * @version 0.1
 * @license http://www.opensource.org/licenses/MIT MIT License
 * @link https://github.com/crossjoin/browscap
 */
class File implements CacheInterface
{
    private static $cache_dir;

    /**
     * Get cached data by a given key
     *
     * @param string $key
     * @param boolean $with_version
     * @return string|null
     */
    public function get($key, $with_version = true)
    {
        $file = $this->getFileName($key, $with_version, false);
        if (is_readable($file)) {
            return file_get_contents($file);
        }
        return null;
    }

    /**
     * Set cached data for a given key
     *
     * @param string $key
     * @param string $content
     * @param boolean $with_version
     * @return int|false
     */
    public function set($key, $content, $with_version = true)
    {
        $file = $this->getFileName($key, $with_version, true);
        return file_put_contents($file, $content);
    }

    /**
     * Delete cached data by a given key
     *
     * @param string  $key
     * @param boolean $with_version
     * @return boolean
     */
    public function delete($key, $with_version = true)
    {
        $file = $this->getFileName($key, $with_version, false);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * Check if a key is already cached
     *
     * @param string  $key
     * @param boolean $with_version
     * @return boolean
     */
    public function exists($key, $with_version = true)
    {
        return file_exists($this->getFileName($key, $with_version, false));
    }

    /**
     * Gets the cache file name for a given key
     *
     * @param string  $key
     * @param boolean $with_version
     * @param boolean $create_dir
     *
     * @return string
     */
    public function getFileName($key, $with_version = true, $create_dir = false)
    {
        $file  = $this->getCacheDirectory($with_version, $create_dir);
        $file .= DIRECTORY_SEPARATOR . $key;

        return $file;
    }

    /**
     * Sets the (main) cache directory
     *
     * @param string $cache_dir
     */
    public static function setCacheDirectory($cache_dir)
    {
        self::$cache_dir = rtrim($cache_dir, DIRECTORY_SEPARATOR);
    }

    /**
     * Gets the main/version cache directory
     *
     * @param boolean $with_version
     * @param boolean $create_dir
     *
     * @return string
     */
    public static function getCacheDirectory($with_version = false, $create_dir = false)
    {
        if (self::$cache_dir === null) {
            self::setCacheDirectory(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'browscap');
        }
        $path = self::$cache_dir;

        if ($with_version === true) {
            $path .= DIRECTORY_SEPARATOR . 'browscap_v' . \phpbrowscap\Browscap::getParser()->getVersion();
        }

        if ($create_dir === true && !file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }
}