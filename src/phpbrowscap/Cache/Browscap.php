<?php

namespace phpbrowscap\Cache;

use phpbrowscap\Exception;
use WurflCache\Adapter\AdapterInterface;

/**
 * Browscap.ini parsing class with caching and update capabilities
 *
 * PHP version 5
 *
 * Copyright (c) 2006-2012 Jonathan Stoppani
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
 * @author     Jonathan Stoppani <jonathan@stoppani.name>
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @author     Mikołaj Misiurewicz <quentin389+phpb@gmail.com>
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/GaretJax/phpbrowscap/
 */
class Browscap implements AdapterInterface
{
    /**
     * Current version of the class.
     */
    const CACHE_FILE_VERSION = '2.0b';

    /**
     * Path to the cache directory
     *
     * @var string
     */
    private $cacheDir = null;

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param string $cache_dir
     *
     * @throws Exception
     */
    public function __construct($cache_dir)
    {
        // has to be set to reach E_STRICT compatibility, does not affect system/app settings
        date_default_timezone_set(date_default_timezone_get());

        if (!isset($cache_dir)) {
            throw new Exception('You have to provide a path to read/store the browscap cache file');
        }

        $old_cache_dir = $cache_dir;
        $cache_dir     = realpath($cache_dir);

        if (false === $cache_dir) {
            throw new Exception(sprintf(
                    'The cache path %s is invalid. Are you sure that it exists and that you have permission to access it?',
                    $old_cache_dir
                ));
        }

        // Is the cache dir really the directory or is it directly the file?
        if (substr($cache_dir, -4) === '.php') {
            $this->cacheFilename = basename($cache_dir);
            $this->cacheDir      = dirname($cache_dir);
        } else {
            $this->cacheDir = $cache_dir;
        }

        $this->cacheDir .= DIRECTORY_SEPARATOR;
    }

    /**
     * Get an item.
     *
     * @param  string $key
     * @param  bool   $success
     *
     * @return mixed Data on success, null on failure
     */
    public function getItem($key, & $success = null)
    {
        if (!$this->hasItem($key)) {
            $success = false;

            return null;
        }

        $source_version = null;
        $browsers       = array();
        $patterns       = array();
        $properties     = array();
        $userAgents     = array();

        require $this->buildCacheFile($key);

        if (!isset($cache_version) || $cache_version != self::CACHE_FILE_VERSION) {
            $success = false;

            return null;
        }

        $data = array(
            'source_version' => $source_version,
            'browsers'       => $browsers,
            'patterns'       => $patterns,
            'properties'     => $properties,
            'userAgents'     => $userAgents
        );

        $success = true;

        return $data;
    }

    /**
     * Store an item.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return bool
     */
    public function setItem($key, $value)
    {
        $cacheTpl = "<?php\n\$source_version=%s;\n\$cache_version=%s;\n\$properties=%s;\n\$browsers=%s;\n\$userAgents=%s;\n\$patterns=%s;\n";

        $propertiesArray = $this->array2string($value['properties']);
        $patternsArray   = $this->array2string($value['patterns']);
        $browsersArray   = $this->array2string($value['browsers']);
        $userAgentsArray = $this->array2string($value['userAgents']);

        $cacheData = sprintf(
            $cacheTpl,
            "'" . $value['source_version'] . "'",
            "'" . self::CACHE_FILE_VERSION . "'",
            $propertiesArray,
            $browsersArray,
            $userAgentsArray,
            $patternsArray
        );

        // Save and return
        return (bool) file_put_contents($this->buildCacheFile($key), $cacheData, LOCK_EX);
    }

    /**
     * Test if an item exists.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function hasItem($key)
    {
        return file_exists($this->buildCacheFile($key));
    }

    /**
     * Remove an item.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function removeItem($key)
    {
        return unlink($this->buildCacheFile($key));
    }

    /**
     * Flush the whole storage
     *
     * @return bool
     */
    public function flush()
    {
        return false;
    }

    /**
     * set the expiration time
     *
     * @param integer $expiration
     *
     * @return AdapterInterface
     */
    public function setExpiration($expiration = 86400)
    {
        // not supported

        return $this;
    }

    /**
     * set the cache namespace
     *
     * @param string $namespace
     *
     * @return AdapterInterface
     */
    public function setNamespace($namespace)
    {
        // not supported

        return $this;
    }

    /**
     * build the name of the cache file
     *
     * @param string $key
     *
     * @return string
     */
    private function buildCacheFile($key)
    {
        return $this->cacheDir . $key . '.php';
    }

    /**
     * Converts the given array to the PHP string which represent it.
     * This method optimizes the PHP code and the output differs form the
     * var_export one as the internal PHP function does not strip whitespace or
     * convert strings to numbers.
     *
     * @param array $array the array to parse and convert
     *
     * @return string the array parsed into a PHP string
     */
    private function array2string($array)
    {
        $strings = array();

        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $key = '';
            } elseif (ctype_digit((string) $key) || '.0' === substr($key, -2)) {
                $key = intval($key) . ' => ';
            } else {
                $key = "'" . str_replace("'", "\'", $key) . "' => ";
            }

            if (is_array($value)) {
                $value = "'" . addcslashes(serialize($value), "'") . "'";
            } elseif (ctype_digit((string) $value)) {
                $value = intval($value);
            } else {
                $value = "'" . str_replace("'", "\'", $value) . "'";
            }

            $strings[] = $key . $value;
        }

        return "array(\n" . implode(",\n", $strings) . "\n)";
    }
}
