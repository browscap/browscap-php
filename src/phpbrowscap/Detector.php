<?php
namespace phpbrowscap;

use FileLoader\Loader;
use Psr\Log\LoggerInterface;
use WurflCache\Adapter\AdapterInterface;
use WurflCache\Adapter\File;

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
class Detector extends AbstractBrowscap
{
    /**
     * The path of the local version of the browscap.ini file from which to
     * update (to be set only if used).
     *
     * @var string
     */
    private $localFile = null;

    /**
     * Flag to enable only lowercase indexes in the result.
     * The cache has to be rebuilt in order to apply this option.
     *
     * @var bool
     */
    private $lowercase = false;

    /**
     * Where to store the cached PHP arrays.
     *
     * @var string
     */
    private $cacheFilename = 'cache.php';

    /**
     * Where to store the downloaded ini file.
     *
     * @var string
     */
    private $iniFilename = 'browscap.ini';

    /**
     * Path to the cache directory
     *
     * @var string
     */
    private $cacheDir = null;

    /**
     * Flag to be set to true after loading the cache
     *
     * @var bool
     */
    private $cacheLoaded = false;

    /**
     * Where to store the value of the included PHP cache file
     *
     * @var array
     */
    private $userAgents = array();
    private $browsers = array();
    private $patterns = array();
    private $properties = array();
    private $sourceVersion = null;

    /**
     * a \WurflCache\Adapter\AdapterInterface object
     *
     * @var AdapterInterface
     */
    private $cache = null;

    /**
     * a \WurflCache\Adapter\AdapterInterface object
     *
     * @var AdapterInterface
     */
    private $parsedCache = null;

    /**
     * an logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /*
     * @var string
     */
    private $cachePrefix = '';

    /*
     * @var IniLoader
     */
    private $loader = null;

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param string $cacheDir
     *
     * @throws Exception
     */
    public function __construct($cacheDir)
    {
        if (!isset($cacheDir)) {
            throw new Exception(
                'You have to provide a path to read/store the browscap cache file',
                Exception::CACHE_DIR_MISSING
            );
        }

        $filename = '';

        if (false === realpath($cacheDir)) {
            /*
             * the cach path is mostly invalid
             * -> check if its really a filename of a not existing file
             */
            if (substr($cacheDir, -4) === '.php'
                || substr($cacheDir, -4) === '.ini'
            ) {
                // extract file name
                $filename = basename($cacheDir);
                $cacheDir = dirname($cacheDir);
            }
        }

        $oldCacheDir = $cacheDir;
        $cacheDir    = realpath($cacheDir);

        if (false === $cacheDir) {
            throw new Exception(
                'The cache path "' . $oldCacheDir . '" is invalid. '
                . 'Are you sure that it exists and that you have permission '
                . 'to access it?',
                Exception::CACHE_DIR_INVALID
            );
        }

        if ('' !== $filename) {
            // The cache dir is a name of an not existing file
            if ($filename && substr($filename, -4) === '.php') {
                $this->cacheFilename = $filename;
            } elseif ($filename && substr($filename, -4) === '.ini') {
                $this->iniFilename   = $filename;
                $this->cacheFilename = $filename;
            }

            $this->cacheDir = $cacheDir;
        } elseif (is_file($cacheDir)) {
            // Is the cache dir really the directory or is it directly the file?
            $filename = basename($cacheDir);

            if ($filename && substr($filename, -4) === '.php') {
                $this->cacheFilename = $filename;
            } elseif ($filename && substr($filename, -4) === '.ini') {
                $this->iniFilename   = $filename;
                $this->cacheFilename = $filename;
            }

            $this->cacheDir = dirname($cacheDir);
        } elseif (is_dir($cacheDir)) {
            $this->cacheDir = $cacheDir;

            if ($filename && substr($filename, -4) === '.php') {
                $this->cacheFilename = $filename;
            } elseif ($filename && substr($filename, -4) === '.ini') {
                $this->iniFilename   = $filename;
                $this->cacheFilename = $filename;
            }
        } else {
            throw new Exception(
                'The cache path "' . $oldCacheDir . '" is invalid. '
                . 'Are you sure that it exists and that you have permission '
                . 'to access it?',
                Exception::CACHE_DIR_INVALID
            );
        }

        if (!is_readable($this->cacheDir)) {
            throw new Exception(
                'Its not possible to read from the given cache path "'
                . $oldCacheDir . '"',
                Exception::CACHE_DIR_NOT_READABLE
            );
        }

        if (!is_writable($this->cacheDir)) {
            throw new Exception(
                'Its not possible to write to the given cache path "'
                . $oldCacheDir . '"',
                Exception::CACHE_DIR_NOT_WRITABLE
            );
        }

        $this->cacheDir .= DIRECTORY_SEPARATOR;

        $this->parsedCache = new Cache\Browscap($this->cacheDir);
    }

    /**
     * @return mixed
     */
    public function getSourceVersion()
    {
        return $this->sourceVersion;
    }

    /**
     * sets the cache used to make the detection faster
     *
     * @param AdapterInterface $cache
     *
     * @return Detector
     */
    public function setCache(AdapterInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * returns the cache used to make the detection faster
     *
     * @return AdapterInterface
     */
    public function getCache()
    {
        if (null === $this->cache) {
            $this->cache = new File(array('dir' => $this->cacheDir));
        }

        return $this->cache;
    }

    /**
     * sets the logger
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return Detector
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * sets the the cache prefix
     *
     * @param string $prefix the new prefix
     *
     * @throws \UnexpectedValueException
     * @return Detector
     */
    public function setCachePrefix($prefix)
    {
        if (!is_string($prefix)) {
            throw new \UnexpectedValueException(
                'the cache prefix has to be a string',
                Exception::STRING_VALUE_EXPECTED
            );
        }

        $this->cachePrefix = $prefix;

        return $this;
    }

    /**
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws Exception
     * @return Detector
     */
    public function setLocaleFile($filename)
    {
        if (empty($filename)) {
            throw new Exception(
                'the filename can not be empty',
                Exception::LOCAL_FILE_MISSING
            );
        }

        $this->localFile = $filename;

        return $this;
    }

    /**
     * XXX parse
     *
     * Gets the information about the browser by User Agent
     *
     * @param string $userAgent   the user agent string
     * @param bool   $returnArray whether return an array or an object
     *
     * @throws Exception
     * @return \StdClass|array  the object containing the browsers details. Array if
     *                    $return_array is set to true.
     */
    public function getBrowser($userAgent = null, $returnArray = false)
    {
        // Load the cache at the first request
        if (!$this->cacheLoaded) {
            $updateCache = true;

            if ($this->loadCache()) {
                $updateCache = false;
            }

            if ($updateCache) {
                $this->updateCache();
            }

            if (!$this->loadCache()) {
                throw new Exception(
                    'Cannot load this cache version - the cache format is not compatible.',
                    Exception::CACHE_INCOMPATIBLE
                );
            }
        }

        // Automatically detect the useragent
        if (!isset($userAgent)) {
            $support   = new Support($_SERVER);
            $userAgent = $support->getUserAgent();
        }

        $quoterHelper = new Helper\Quoter();

        $browser = array();
        foreach ($this->patterns as $pattern => $patternData) {
            if (!$pattern) {
                continue;
            }

            $matches = array();

            if (!preg_match($pattern . 'i', $userAgent, $matches)) {
                continue;
            }

            if (1 == count($matches)) {
                // standard match
                $key = $patternData;

                $simpleMatch = true;
            } else {
                $patternData = unserialize($patternData);

                // match with numeric replacements
                array_shift($matches);

                $matchString = self::COMPRESSION_PATTERN_START . implode(
                        self::COMPRESSION_PATTERN_DELIMITER,
                        $matches
                    );

                if (!isset($patternData[$matchString])) {
                    // partial match - numbers are not present, but everything else is ok
                    continue;
                }

                $key = $patternData[$matchString];

                $simpleMatch = false;
            }

            $browser = array(
                $userAgent, // Original useragent
                trim(strtolower($pattern), self::REGEX_DELIMITER),
                $quoterHelper->pregUnQuote($pattern, $simpleMatch ? false : $matches)
            );

            $browser = $value = $browser + unserialize($this->browsers[$key]);

            while (array_key_exists(3, $value)) {
                $value = unserialize($this->browsers[$value[3]]);
                $browser += $value;
            }

            if (!empty($browser[3])) {
                $browser[3] = $this->userAgents[$browser[3]];
            }

            break;
        }

        // Add the keys for each property
        $array = array();
        foreach ($browser as $key => $value) {
            if ($value === 'true') {
                $value = true;
            } elseif ($value === 'false') {
                $value = false;
            }
            $array[$this->properties[$key]] = $value;
        }

        return $returnArray ? $array : (object) $array;
    }

    /**
     * XXX save
     *
     * Parses the ini file and updates the cache files
     *
     * @throws Exception
     * @return bool whether the file was correctly written to the disk
     */
    public function updateCache()
    {
        // load parsed ini file from cache
        $success  = false;
        $cacheId  = $this->cachePrefix . $this->iniFilename . '.loaded';
        $browsers = $this->getCache()->getItem($cacheId, $success);

        if (!$success) {
            // loading parsed file failed
            // -> load from remote and parse
            $browsers = $this->getLoader()->load();

            // store loaded file to the cache
            $this->getCache()->setItem($cacheId, $browsers);
        }

        if (!is_array($browsers)) {
            throw new Exception('loading the content from cache and from remote failed');
        }

        $this->sourceVersion = $browsers[self::BROWSCAP_VERSION_KEY]['Version'];
        unset($browsers[self::BROWSCAP_VERSION_KEY]);
        unset($browsers['DefaultProperties']['RenderingEngine_Description']);

        $this->properties = array_keys($browsers['DefaultProperties']);

        array_unshift(
            $this->properties,
            'browser_name',
            'browser_name_regex',
            'browser_name_pattern',
            'Parent'
        );

        $tmpUserAgents = array_keys($browsers);
        $sorterHelper  = new Helper\Sorter();

        usort($tmpUserAgents, array($sorterHelper, 'compareBcStrings'));

        $user_agents_keys = array_flip($tmpUserAgents);
        $properties_keys  = array_flip($this->properties);

        $tmpPatterns = array();

        foreach ($tmpUserAgents as $i => $user_agent) {
            if (strpos($user_agent, '*') !== false || strpos($user_agent, '?') !== false) {
                $quoterHelper = new Helper\Quoter();
                $pattern      = $quoterHelper->pregQuote($user_agent);

                $matchesCount = preg_match_all(
                    self::COMPRESSION_PATTERN_START . '\d' . self::COMPRESSION_PATTERN_START,
                    $pattern,
                    $matches
                );

                if (!$matchesCount) {
                    $tmpPatterns[$pattern] = $i;
                } else {
                    $compressed_pattern = preg_replace(
                        self::COMPRESSION_PATTERN_START . '\d' . self::COMPRESSION_PATTERN_START,
                        '(\d)',
                        $pattern
                    );

                    if (!isset($tmpPatterns[$compressed_pattern])) {
                        $tmpPatterns[$compressed_pattern] = array('first' => $pattern);
                    }

                    $tmpPatterns[$compressed_pattern][$i] = $matches[0];
                }
            }

            if (!empty($browsers[$user_agent]['Parent'])) {
                $parent                               = $browsers[$user_agent]['Parent'];
                $parent_key                           = $user_agents_keys[$parent];
                $browsers[$user_agent]['Parent']      = $parent_key;
                $this->userAgents[$parent_key . '.0'] = $tmpUserAgents[$parent_key];
            };

            $browser = array();
            foreach ($browsers[$user_agent] as $key => $value) {
                if (!isset($properties_keys[$key])) {
                    continue;
                }

                $key           = $properties_keys[$key];
                $browser[$key] = $value;
            }

            $this->browsers[] = $browser;
        }

        foreach ($tmpPatterns as $pattern => $pattern_data) {
            if (is_int($pattern_data)) {
                $this->patterns[$pattern] = $pattern_data;
            } elseif (2 == count($pattern_data)) {
                end($pattern_data);
                $this->patterns[$pattern_data['first']] = key($pattern_data);
            } else {
                unset($pattern_data['first']);

                $pattern_data = $this->deduplicateCompressionPattern($pattern_data, $pattern);

                $this->patterns[$pattern] = $pattern_data;
            }
        }

        // Save the keys lowercased if needed
        if ($this->lowercase) {
            $this->properties = array_map('strtolower', $this->properties);
        }

        $cache = array(
            'source_version' => $this->sourceVersion,
            'browsers'       => $this->browsers,
            'patterns'       => $this->patterns,
            'properties'     => $this->properties,
            'userAgents'     => $this->userAgents
        );

        // Save and return
        return $this->parsedCache->setItem($this->cachePrefix . $this->cacheFilename, $cache);
    }

    /**
     * Loads the cache into object's properties
     *
     * @return boolean
     */
    private function loadCache()
    {
        $this->cacheLoaded = false;

        $success = false;
        $content = $this->parsedCache->getItem($this->cachePrefix . $this->cacheFilename, $success);

        if ($success) {
            $this->sourceVersion = $content['source_version'];
            $this->browsers      = $content['browsers'];
            $this->patterns      = $content['patterns'];
            $this->properties    = $content['properties'];
            $this->userAgents    = $content['userAgents'];

            $this->cacheLoaded = true;
        }

        return $success;
    }

    /**
     * returns the ini loader
     *
     * @return IniLoader
     */
    public function getLoader()
    {
        if (null === $this->loader) {
            $this->loader = new IniLoader($this->cacheDir);
        }

        if (null !== $this->localFile) {
            $this->loader->setLocaleFile($this->localFile);
            $this->loader->setIniFile(basename($this->localFile));
        } else {
            $this->loader->setIniFile($this->iniFilename);
        }

        return $this->loader;
    }

    /**
     * sets the loader
     *
     * @param Loader $loader
     *
     * @return Detector
     */
    public function setLoader(Loader $loader)
    {
        $this->getLoader()->setLoader($loader);

        return $this;
    }
}
