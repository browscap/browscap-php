<?php

namespace phpbrowscap;

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
class Browscap
{
    /**
     * Current version of the class.
     */
    const VERSION = '2.0.4';

    const CACHE_FILE_VERSION = '2.0.4';

    /**
     * Different ways to access remote and local files.
     *
     * UPDATE_FOPEN: Uses the fopen url wrapper (use file_get_contents).
     * UPDATE_FSOCKOPEN: Uses the socket functions (fsockopen).
     * UPDATE_CURL: Uses the cURL extension.
     * UPDATE_LOCAL: Updates from a local file (file_get_contents).
     */
    const UPDATE_FOPEN = 'URL-wrapper';
    const UPDATE_FSOCKOPEN = 'socket';
    const UPDATE_CURL = 'cURL';
    const UPDATE_LOCAL = 'local';

    /**
     * Options for regex patterns.
     *
     * REGEX_DELIMITER: Delimiter of all the regex patterns in the whole class.
     * REGEX_MODIFIERS: Regex modifiers.
     */
    const REGEX_DELIMITER = '@';
    const REGEX_MODIFIERS = 'i';
    const COMPRESSION_PATTERN_START = '@';
    const COMPRESSION_PATTERN_DELIMITER = '|';

    /**
     * The values to quote in the ini file
     */
    const VALUES_TO_QUOTE = 'Browser|Parent';

    const BROWSCAP_VERSION_KEY = 'GJK_Browscap_Version';

    /**
     * The headers to be sent for checking the version and requesting the file.
     */
    const REQUEST_HEADERS = "GET %s HTTP/1.0\r\nHost: %s\r\nUser-Agent: %s\r\nConnection: Close\r\n\r\n";

    /**
     * Options for auto update capabilities
     *
     * $remoteVerUrl: The location to use to check out if a new version of the
     *                browscap.ini file is available.
     * $remoteIniUrl: The location from which download the ini file.
     *                The placeholder for the file should be represented by a %s.
     * $timeout: The timeout for the requests.
     * $updateInterval: The update interval in seconds.
     * $errorInterval: The next update interval in seconds in case of an error.
     * $doAutoUpdate: Flag to disable the automatic interval based update.
     * $updateMethod: The method to use to update the file, has to be a value of
     *                an UPDATE_* constant, null or false.
     *
     * The default source file type is changed from normal to full. The performance difference
     * is MINIMAL, so there is no reason to use the standard file whatsoever. Either go for light,
     * which is blazing fast, or get the full one. (note: light version doesn't work, a fix is on its way)
     */
    public $remoteIniUrl = 'http://browscap.org/stream?q=PHP_BrowscapINI';
    public $remoteVerUrl = 'http://browscap.org/version';
    public $timeout = 5;
    public $updateInterval = 432000; // 5 days
    public $errorInterval = 7200; // 2 hours
    public $doAutoUpdate = true;
    public $updateMethod = null;

    /**
     * The path of the local version of the browscap.ini file from which to
     * update (to be set only if used).
     *
     * @var string
     */
    public $localFile = null;

    /**
     * The useragent to include in the requests made by the class during the
     * update process.
     *
     * @var string
     */
    public $userAgent = 'Browser Capabilities Project - PHP Browscap/%v %m';

    /**
     * Flag to enable only lowercase indexes in the result.
     * The cache has to be rebuilt in order to apply this option.
     *
     * @var bool
     */
    public $lowercase = false;

    /**
     * Flag to enable/disable silent error management.
     * In case of an error during the update process the class returns an empty
     * array/object if the update process can't take place and the browscap.ini
     * file does not exist.
     *
     * @var bool
     */
    public $silent = false;

    /**
     * Where to store the cached PHP arrays.
     *
     * @var string
     */
    public $cacheFilename = 'cache.php';

    /**
     * Where to store the downloaded ini file.
     *
     * @var string
     */
    public $iniFilename = 'browscap.ini';

    /**
     * Path to the cache directory
     *
     * @var string
     */
    public $cacheDir = null;

    /**
     * Flag to be set to true after loading the cache
     *
     * @var bool
     */
    protected $_cacheLoaded = false;

    /**
     * Where to store the value of the included PHP cache file
     *
     * @var array
     */
    protected $_userAgents = array();
    protected $_browsers = array();
    protected $_patterns = array();
    protected $_properties = array();
    protected $_source_version;

    /**
     * An associative array of associative arrays in the format
     * `$arr['wrapper']['option'] = $value` passed to stream_context_create()
     * when building a stream resource.
     *
     * Proxy settings are stored in this variable.
     *
     * @see http://www.php.net/manual/en/function.stream-context-create.php
     *
     * @var array
     */
    protected $_streamContextOptions = array();

    /**
     * A valid context resource created with stream_context_create().
     *
     * @see http://www.php.net/manual/en/function.stream-context-create.php
     *
     * @var resource
     */
    protected $_streamContext = null;

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
            throw new Exception(
                sprintf(
                    'The cache path %s is invalid. Are you sure that it exists and that you have permission to access it?',
                    $old_cache_dir
                )
            );
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
     * @return mixed
     */
    public function getSourceVersion()
    {
        return $this->_source_version;
    }

    /**
     * XXX parse
     *
     * Gets the information about the browser by User Agent
     *
     * @param string $user_agent   the user agent string
     * @param bool   $return_array whether return an array or an object
     *
     * @throws Exception
     * @return \stdClass|array  the object containing the browsers details. Array if
     *                    $return_array is set to true.
     */
    public function getBrowser($user_agent = null, $return_array = false)
    {
        // Load the cache at the first request
        if (!$this->_cacheLoaded) {
            $cache_file = $this->cacheDir . $this->cacheFilename;
            $ini_file   = $this->cacheDir . $this->iniFilename;

            // Set the interval only if needed
            if ($this->doAutoUpdate && file_exists($ini_file)) {
                $interval = time() - filemtime($ini_file);
            } else {
                $interval = 0;
            }

            $update_cache = true;

            if (file_exists($cache_file) && file_exists($ini_file) && ($interval <= $this->updateInterval)) {
                if ($this->_loadCache($cache_file)) {
                    $update_cache = false;
                }
            }

            if ($update_cache) {
                try {
                    $this->updateCache();
                } catch (Exception $e) {
                    if (file_exists($ini_file)) {
                        // Adjust the filemtime to the $errorInterval
                        touch($ini_file, time() - $this->updateInterval + $this->errorInterval);
                    } elseif ($this->silent) {
                        // Return an array if silent mode is active and the ini db doesn't exsist
                        return array();
                    }

                    if (!$this->silent) {
                        throw $e;
                    }
                }

                if (!$this->_loadCache($cache_file)) {
                    throw new Exception('Cannot load this cache version - the cache format is not compatible.');
                }
            }
        }

        // Automatically detect the useragent
        if (!isset($user_agent)) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
            } else {
                $user_agent = '';
            }
        }

        $browser = array();
        foreach ($this->_patterns as $pattern => $pattern_data) {
            if (preg_match($pattern . 'i', $user_agent, $matches)) {
                if (1 == count($matches)) {
                    // standard match
                    $key = $pattern_data;

                    $simple_match = true;
                } else {
                    $pattern_data = unserialize($pattern_data);

                    // match with numeric replacements
                    array_shift($matches);

                    $match_string = self::COMPRESSION_PATTERN_START
                        . implode(self::COMPRESSION_PATTERN_DELIMITER, $matches);

                    if (!isset($pattern_data[$match_string])) {
                        // partial match - numbers are not present, but everything else is ok
                        continue;
                    }

                    $key = $pattern_data[$match_string];

                    $simple_match = false;
                }

                $browser = array(
                    $user_agent, // Original useragent
                    trim(strtolower($pattern), self::REGEX_DELIMITER),
                    $this->_pregUnQuote($pattern, $simple_match ? false : $matches)
                );

                $browser = $value = $browser + unserialize($this->_browsers[$key]);

                while (array_key_exists(3, $value)) {
                    $value = unserialize($this->_browsers[$value[3]]);
                    $browser += $value;
                }

                if (!empty($browser[3]) && array_key_exists($browser[3], $this->_userAgents)) {
                    $browser[3] = $this->_userAgents[$browser[3]];
                }

                break;
            }
        }

        // Add the keys for each property
        $array = array();
        foreach ($browser as $key => $value) {
            if ($value === 'true') {
                $value = true;
            } elseif ($value === 'false') {
                $value = false;
            }

            $tmp_key = $this->_properties[$key];
            if ($this->lowercase) {
                $tmp_key = strtolower($this->_properties[$key]);
            }
            $array[$tmp_key] = $value;
        }

        return $return_array ? $array : (object) $array;
    }

    /**
     * Load (auto-set) proxy settings from environment variables.
     */
    public function autodetectProxySettings()
    {
        $wrappers = array('http', 'https', 'ftp');

        foreach ($wrappers as $wrapper) {
            $url = getenv($wrapper . '_proxy');
            if (!empty($url)) {
                $params = array_merge(
                    array(
                        'port' => null,
                        'user' => null,
                        'pass' => null,
                    ),
                    parse_url($url)
                );
                $this->addProxySettings($params['host'], $params['port'], $wrapper, $params['user'], $params['pass']);
            }
        }
    }

    /**
     * Add proxy settings to the stream context array.
     *
     * @param string $server   Proxy server/host
     * @param int    $port     Port
     * @param string $wrapper  Wrapper: "http", "https", "ftp", others...
     * @param string $username Username (when requiring authentication)
     * @param string $password Password (when requiring authentication)
     *
     * @return Browscap
     */
    public function addProxySettings($server, $port = 3128, $wrapper = 'http', $username = null, $password = null)
    {
        $settings = array(
            $wrapper => array(
                'proxy'             => sprintf('tcp://%s:%d', $server, $port),
                'request_fulluri'   => true,
                'timeout'           => $this->timeout,
            )
        );

        // Proxy authentication (optional)
        if (isset($username) && isset($password)) {
            $settings[$wrapper]['header'] = 'Proxy-Authorization: Basic ' . base64_encode($username . ':' . $password);
        }

        // Add these new settings to the stream context options array
        $this->_streamContextOptions = array_merge(
            $this->_streamContextOptions,
            $settings
        );

        /* Return $this so we can chain addProxySettings() calls like this:
         * $browscap->
         *   addProxySettings('http')->
         *   addProxySettings('https')->
         *   addProxySettings('ftp');
         */
        return $this;
    }

    /**
     * Clear proxy settings from the stream context options array.
     *
     * @param string $wrapper Remove settings from this wrapper only
     *
     * @return array Wrappers cleared
     */
    public function clearProxySettings($wrapper = null)
    {
        $wrappers = isset($wrapper) ? array($wrapper) : array_keys($this->_streamContextOptions);

        $clearedWrappers = array();
        $options         = array('proxy', 'request_fulluri', 'header');
        foreach ($wrappers as $wrapper) {

            // remove wrapper options related to proxy settings
            if (isset($this->_streamContextOptions[$wrapper]['proxy'])) {
                foreach ($options as $option) {
                    unset($this->_streamContextOptions[$wrapper][$option]);
                }

                // remove wrapper entry if there are no other options left
                if (empty($this->_streamContextOptions[$wrapper])) {
                    unset($this->_streamContextOptions[$wrapper]);
                }

                $clearedWrappers[] = $wrapper;
            }
        }

        return $clearedWrappers;
    }

    /**
     * Returns the array of stream context options.
     *
     * @return array
     */
    public function getStreamContextOptions()
    {
        $streamContextOptions = $this->_streamContextOptions;

        if (empty($streamContextOptions)) {
            // set default context, including timeout
            $streamContextOptions = array(
                'http' => array(
                    'timeout' => $this->timeout,
                )
            );
        }

        return $streamContextOptions;
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
        $lockfile = $this->cacheDir . 'cache.lock';

        if (file_exists($lockfile) || !touch($lockfile)) {
            throw new Exception('temporary file already exists');
        }

        $ini_path   = $this->cacheDir . $this->iniFilename;
        $cache_path = $this->cacheDir . $this->cacheFilename;

        // Choose the right url
        if ($this->_getUpdateMethod() == self::UPDATE_LOCAL) {
            $url = $this->localFile;
        } else {
            $url = $this->remoteIniUrl;
        }

        $this->_getRemoteIniFile($url, $ini_path);

        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $browsers = parse_ini_file($ini_path, true, INI_SCANNER_RAW);
        } else {
            $browsers = parse_ini_file($ini_path, true);
        }

        $this->_source_version = $browsers[self::BROWSCAP_VERSION_KEY]['Version'];
        unset($browsers[self::BROWSCAP_VERSION_KEY]);

        unset($browsers['DefaultProperties']['RenderingEngine_Description']);

        $this->_properties = array_keys($browsers['DefaultProperties']);

        array_unshift(
            $this->_properties,
            'browser_name',
            'browser_name_regex',
            'browser_name_pattern',
            'Parent'
        );

        $tmp_user_agents = array_keys($browsers);

        usort($tmp_user_agents, array($this, 'compareBcStrings'));

        $user_agents_keys = array_flip($tmp_user_agents);
        $properties_keys  = array_flip($this->_properties);

        $tmp_patterns = array();

        foreach ($tmp_user_agents as $i => $user_agent) {

            if (empty($browsers[$user_agent]['Comment'])
                || false !== strpos($user_agent, '*')
                || false !== strpos($user_agent, '?')
            ) {
                $pattern = $this->_pregQuote($user_agent);

                $matches_count = preg_match_all('@\d@', $pattern, $matches);

                if (!$matches_count) {
                    $tmp_patterns[$pattern] = $i;
                } else {
                    $compressed_pattern = preg_replace('@\d@', '(\d)', $pattern);

                    if (!isset($tmp_patterns[$compressed_pattern])) {
                        $tmp_patterns[$compressed_pattern] = array('first' => $pattern);
                    }

                    $tmp_patterns[$compressed_pattern][$i] = $matches[0];
                }
            }

            if (!empty($browsers[$user_agent]['Parent'])) {
                $parent = $browsers[$user_agent]['Parent'];

                $parent_key = $user_agents_keys[$parent];

                $browsers[$user_agent]['Parent']       = $parent_key;
                $this->_userAgents[$parent_key . '.0'] = $tmp_user_agents[$parent_key];
            };

            $browser = array();
            foreach ($browsers[$user_agent] as $key => $value) {
                if (!isset($properties_keys[$key])) {
                    continue;
                }

                $key           = $properties_keys[$key];
                $browser[$key] = $value;
            }

            $this->_browsers[] = $browser;
        }

        // reducing memory usage by unsetting $tmp_user_agents
        unset($tmp_user_agents);

        foreach ($tmp_patterns as $pattern => $pattern_data) {
            if (is_int($pattern_data)) {
                $this->_patterns[$pattern] = $pattern_data;
            } elseif (2 == count($pattern_data)) {
                end($pattern_data);
                $this->_patterns[$pattern_data['first']] = key($pattern_data);
            } else {
                unset($pattern_data['first']);

                $pattern_data = $this->deduplicateCompressionPattern($pattern_data, $pattern);

                $this->_patterns[$pattern] = $pattern_data;
            }
        }

        // Get the whole PHP code
        $cache = $this->_buildCache();
        $dir   = dirname($cache_path);

        // "tempnam" did not work with VFSStream for tests
        $tmpFile = $dir . '/temp_' . md5(time() . basename($cache_path));

        // asume that all will be ok
        if (false === file_put_contents($tmpFile, $cache)) {
            // writing to the temparary file failed
            throw new Exception('wrting to temporary file failed');
        }

        if (false === rename($tmpFile, $cache_path)) {
            // renaming file failed, remove temp file
            @unlink($tmpFile);

            throw new Exception('could not rename temporary file to the cache file');
        }

        @unlink($lockfile);

        return true;
    }

    /**
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    protected function compareBcStrings($a, $b)
    {
        $a_len = strlen($a);
        $b_len = strlen($b);

        if ($a_len > $b_len) {
            return -1;
        }

        if ($a_len < $b_len) {
            return 1;
        }

        $a_len = strlen(str_replace(array('*', '?'), '', $a));
        $b_len = strlen(str_replace(array('*', '?'), '', $b));

        if ($a_len > $b_len) {
            return -1;
        }

        if ($a_len < $b_len) {
            return 1;
        }

        return 0;
    }

    /**
     * That looks complicated...
     *
     * All numbers are taken out into $matches, so we check if any of those numbers are identical
     * in all the $matches and if they are we restore them to the $pattern, removing from the $matches.
     * This gives us patterns with "(\d)" only in places that differ for some matches.
     *
     * @param array  $matches
     * @param string $pattern
     *
     * @return array of $matches
     */
    protected function deduplicateCompressionPattern($matches, &$pattern)
    {
        $tmp_matches = $matches;
        $first_match = array_shift($tmp_matches);
        $differences = array();

        foreach ($tmp_matches as $some_match) {
            $differences += array_diff_assoc($first_match, $some_match);
        }

        $identical = array_diff_key($first_match, $differences);

        $prepared_matches = array();

        foreach ($matches as $i => $some_match) {
            $key = self::COMPRESSION_PATTERN_START
                . implode(self::COMPRESSION_PATTERN_DELIMITER, array_diff_assoc($some_match, $identical));

            $prepared_matches[$key] = $i;
        }

        $pattern_parts = explode('(\d)', $pattern);

        foreach ($identical as $position => $value) {
            $pattern_parts[$position + 1] = $pattern_parts[$position] . $value . $pattern_parts[$position + 1];
            unset($pattern_parts[$position]);
        }

        $pattern = implode('(\d)', $pattern_parts);

        return $prepared_matches;
    }

    /**
     * Converts browscap match patterns into preg match patterns.
     *
     * @param string $user_agent
     *
     * @return string
     */
    protected function _pregQuote($user_agent)
    {
        $pattern = preg_quote($user_agent, self::REGEX_DELIMITER);

        // the \\x replacement is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match

        return self::REGEX_DELIMITER
            . '^'
            . str_replace(array('\*', '\?', '\\x'), array('.*', '.', '\\\\x'), $pattern)
            . '$'
            . self::REGEX_DELIMITER;
    }

    /**
     * Converts preg match patterns back to browscap match patterns.
     *
     * @param string        $pattern
     * @param array|boolean $matches
     *
     * @return string
     */
    protected function _pregUnQuote($pattern, $matches)
    {
        // list of escaped characters: http://www.php.net/manual/en/function.preg-quote.php
        // to properly unescape '?' which was changed to '.', I replace '\.' (real dot) with '\?', then change '.' to '?' and then '\?' to '.'.
        $search  = array(
            '\\' . self::REGEX_DELIMITER, '\\.', '\\\\', '\\+', '\\[', '\\^', '\\]', '\\$', '\\(', '\\)', '\\{', '\\}',
            '\\=', '\\!', '\\<', '\\>', '\\|', '\\:', '\\-', '.*', '.', '\\?'
        );
        $replace = array(
            self::REGEX_DELIMITER, '\\?', '\\', '+', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|',
            ':', '-', '*', '?', '.'
        );

        $result = substr(str_replace($search, $replace, $pattern), 2, -2);

        if ($matches) {
            foreach ($matches as $one_match) {
                $num_pos = strpos($result, '(\d)');
                $result  = substr_replace($result, $one_match, $num_pos, 4);
            }
        }

        return $result;
    }

    /**
     * Loads the cache into object's properties
     *
     * @param string $cache_file
     *
     * @return boolean
     */
    protected function _loadCache($cache_file)
    {
        $cache_version  = null;
        $source_version = null;
        $browsers       = array();
        $userAgents     = array();
        $patterns       = array();
        $properties     = array();

        $this->_cacheLoaded = false;

        require $cache_file;

        if (!isset($cache_version) || $cache_version != self::CACHE_FILE_VERSION) {
            return false;
        }

        $this->_source_version = $source_version;
        $this->_browsers       = $browsers;
        $this->_userAgents     = $userAgents;
        $this->_patterns       = $patterns;
        $this->_properties     = $properties;

        $this->_cacheLoaded = true;

        return true;
    }

    /**
     * Parses the array to cache and creates the PHP string to write to disk
     *
     * @return string the PHP string to save into the cache file
     */
    protected function _buildCache()
    {
        $cacheTpl = "<?php\n\$source_version=%s;\n\$cache_version=%s;\n\$properties=%s;\n\$browsers=%s;\n\$userAgents=%s;\n\$patterns=%s;\n";

        $propertiesArray = $this->_array2string($this->_properties);
        $patternsArray   = $this->_array2string($this->_patterns);
        $userAgentsArray = $this->_array2string($this->_userAgents);
        $browsersArray   = $this->_array2string($this->_browsers);

        return sprintf(
            $cacheTpl,
            "'" . $this->_source_version . "'",
            "'" . self::CACHE_FILE_VERSION . "'",
            $propertiesArray,
            $browsersArray,
            $userAgentsArray,
            $patternsArray
        );
    }

    /**
     * Lazy getter for the stream context resource.
     *
     * @param bool $recreate
     *
     * @return resource
     */
    protected function _getStreamContext($recreate = false)
    {
        if (!isset($this->_streamContext) || true === $recreate) {
            $this->_streamContext = stream_context_create($this->getStreamContextOptions());
        }

        return $this->_streamContext;
    }

    /**
     * Updates the local copy of the ini file (by version checking) and adapts
     * his syntax to the PHP ini parser
     *
     * @param string $url  the url of the remote server
     * @param string $path the path of the ini file to update
     *
     * @throws Exception
     * @return bool if the ini file was updated
     */
    protected function _getRemoteIniFile($url, $path)
    {
        // Check version
        if (file_exists($path) && filesize($path)) {
            $local_tmstp = filemtime($path);

            if ($this->_getUpdateMethod() == self::UPDATE_LOCAL) {
                $remote_tmstp = $this->_getLocalMTime();
            } else {
                $remote_tmstp = $this->_getRemoteMTime();
            }

            if ($remote_tmstp < $local_tmstp) {
                // No update needed, return
                touch($path);

                return false;
            }
        }

        if ($url != $path) {
            // Check if it's possible to write to the .ini file.
            if (is_file($path)) {
                if (!is_writable($path)) {
                    throw new Exception(
                        'Could not write to "' . $path . '" (check the permissions of the current/old ini file).'
                    );
                }
            } else {
                // Test writability by creating a file only if one already doesn't exist, so we can safely delete it after the test.
                $test_file = fopen($path, 'a');
                if ($test_file) {
                    fclose($test_file);
                    unlink($path);
                } else {
                    throw new Exception(
                        'Could not write to "' . $path . '" (check the permissions of the cache directory).'
                    );
                }
            }

            // Get updated .ini file
            $browscap = $this->_getRemoteData($url);
            $browscap = explode("\n", $browscap);
            $pattern  = self::REGEX_DELIMITER . '(' . self::VALUES_TO_QUOTE . ')="?([^"]*)"?$' . self::REGEX_DELIMITER;

            // Ok, lets read the file
            $content = '';
            foreach ($browscap as $subject) {
                $subject = trim($subject);
                $content .= preg_replace($pattern, '$1="$2"', $subject) . "\n";
            }

            if (!file_put_contents($path, $content)) {
                throw new Exception("Could not write .ini content to $path");
            }
        }

        return true;
    }

    /**
     * Gets the remote ini file update timestamp
     *
     * @throws Exception
     * @return int the remote modification timestamp
     */
    protected function _getRemoteMTime()
    {
        $remote_datetime = $this->_getRemoteData($this->remoteVerUrl);
        $remote_tmstp    = strtotime($remote_datetime);

        if (!$remote_tmstp) {
            throw new Exception("Bad datetime format from {$this->remoteVerUrl}");
        }

        return $remote_tmstp;
    }

    /**
     * Gets the local ini file update timestamp
     *
     * @throws Exception
     * @return int the local modification timestamp
     */
    protected function _getLocalMTime()
    {
        if (!is_readable($this->localFile) || !is_file($this->localFile)) {
            throw new Exception('Local file is not readable');
        }

        return filemtime($this->localFile);
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
    protected function _array2string($array)
    {
        $strings = array();

        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $key = '';
            } elseif (ctype_digit((string) $key) || '.0' === substr($key, -2)) {
                $key = intval($key) . '=>';
            } else {
                $key = "'" . str_replace("'", "\'", $key) . "'=>";
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

    /**
     * Checks for the various possibilities offered by the current configuration
     * of PHP to retrieve external HTTP data
     *
     * @return string|false the name of function to use to retrieve the file or false if no methods are available
     */
    protected function _getUpdateMethod()
    {
        // Caches the result
        if ($this->updateMethod === null) {
            if ($this->localFile !== null) {
                $this->updateMethod = self::UPDATE_LOCAL;
            } elseif (ini_get('allow_url_fopen') && function_exists('file_get_contents')) {
                $this->updateMethod = self::UPDATE_FOPEN;
            } elseif (function_exists('fsockopen')) {
                $this->updateMethod = self::UPDATE_FSOCKOPEN;
            } elseif (extension_loaded('curl')) {
                $this->updateMethod = self::UPDATE_CURL;
            } else {
                $this->updateMethod = false;
            }
        }

        return $this->updateMethod;
    }

    /**
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     *
     * @throws Exception
     * @return string the retrieved data
     */
    protected function _getRemoteData($url)
    {
        ini_set('user_agent', $this->_getUserAgent());

        switch ($this->_getUpdateMethod()) {
            case self::UPDATE_LOCAL:
                $file = file_get_contents($url);

                if ($file !== false) {
                    return $file;
                } else {
                    throw new Exception('Cannot open the local file');
                }
            case self::UPDATE_FOPEN:
                if (ini_get('allow_url_fopen') && function_exists('file_get_contents')) {
                    // include proxy settings in the file_get_contents() call
                    $context = $this->_getStreamContext();
                    $file    = file_get_contents($url, false, $context);

                    if ($file !== false) {
                        return $file;
                    }
                }// else try with the next possibility (break omitted)
            case self::UPDATE_FSOCKOPEN:
                if (function_exists('fsockopen')) {
                    $remote_url     = parse_url($url);
                    $contextOptions = $this->getStreamContextOptions();

                    $errno   = 0;
                    $errstr  = '';

                    if (empty($contextOptions)) {
                        $port           = (empty($remote_url['port']) ? 80 : $remote_url['port']);
                        $remote_handler = fsockopen($remote_url['host'], $port, $errno, $errstr, $this->timeout);
                    } else {
                        $context = $this->_getStreamContext();

                        $remote_handler = stream_socket_client(
                            $url, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context
                        );
                    }

                    if ($remote_handler) {
                        stream_set_timeout($remote_handler, $this->timeout);

                        if (isset($remote_url['query'])) {
                            $remote_url['path'] .= '?' . $remote_url['query'];
                        }

                        $out = sprintf(
                            self::REQUEST_HEADERS,
                            $remote_url['path'],
                            $remote_url['host'],
                            $this->_getUserAgent()
                        );

                        fwrite($remote_handler, $out);

                        $response = fgets($remote_handler);
                        if (strpos($response, '200 OK') !== false) {
                            $file = '';
                            while (!feof($remote_handler)) {
                                $file .= fgets($remote_handler);
                            }

                            $file = str_replace("\r\n", "\n", $file);
                            $file = explode("\n\n", $file);
                            array_shift($file);

                            $file = implode("\n\n", $file);

                            fclose($remote_handler);

                            return $file;
                        }
                    }
                }// else try with the next possibility
            case self::UPDATE_CURL:
                if (extension_loaded('curl')) { // make sure curl is loaded
                    $ch = curl_init($url);

                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
                    curl_setopt($ch, CURLOPT_USERAGENT, $this->_getUserAgent());

                    $file = curl_exec($ch);

                    curl_close($ch);

                    if ($file !== false) {
                        return $file;
                    }
                }// else try with the next possibility
            case false:
                throw new Exception('Your server can\'t connect to external resources. Please update the file manually.');
        }

        return '';
    }

    /**
     * Format the useragent string to be used in the remote requests made by the
     * class during the update process.
     *
     * @return string the formatted user agent
     */
    protected function _getUserAgent()
    {
        $ua = str_replace('%v', self::VERSION, $this->userAgent);
        $ua = str_replace('%m', $this->_getUpdateMethod(), $ua);

        return $ua;
    }
}

/**
 * Browscap.ini parsing class exception
 *
 * @package    Browscap
 * @author     Jonathan Stoppani <jonathan@stoppani.name>
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/GaretJax/phpbrowscap/
 */
class Exception extends \Exception
{
    // nothing to do here
}
