<?php
namespace phpbrowscap\Parser;

use phpbrowscap\Cache\CacheInterface;
use phpbrowscap\Cache\File;
use phpbrowscap\Formatter\FormatterInterface;
use phpbrowscap\Formatter\PhpGetBrowser;

/**
 * Ini parser class (compatible with PHP 5.3+)
 *
 * This parser uses the standard PHP browscap.ini as its source. It requires
 * the file cache, because in most cases we work with files line by line
 * instead of using arrays, to keep the memory consumption as low as possible.
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
class Ini extends AbstractParser
{
    /**
     * The key to search for in the INI file to find the browscap settings
     */
    const BROWSCAP_VERSION_KEY = 'GJK_Browscap_Version';

    /**
     * The type to use when downloading the browscap source data
     *
     * @var string
     */
    protected $sourceType = 'PHP_BrowscapINI';

    /**
     * Number of pattern to combine for a faster regular expression search.
     *
     * @important The number of patterns that can be processed in one step
     *            is limited by the internal regular expression limits.
     * @var int
     */
    protected $joinPatterns = 100;

    /**
     * Detected browscap version (read from INI file)
     *
     * @var int
     */
    protected static $version;

    /**
     * The cache instance
     *
     * @var \phpbrowscap\Cache\\phpbrowscap\Cache\CacheInterface
     */
    private $cache = null;

    /**
     * @var Helper\GetPatternInterface
     */
    private $helper = null;

    /**
     * Formatter to use
     *
     * @var \phpbrowscap\Formatter\FormatterInterface
     */
    private $formatter = null;

    /**
     * @param \phpbrowscap\Parser\Helper\GetPatternInterface $helper
     *
     * @return \phpbrowscap\Parser\Ini
     */
    public function setHelper($helper)
    {
        $this->helper = $helper;

        return $this;
    }

    /**
     * @return \phpbrowscap\Parser\Helper\GetPatternInterface
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Set theformatter instance to use for the getBrowser() result
     *
     * @param \phpbrowscap\Formatter\FormatterInterface $formatter
     *
     * @return \phpbrowscap\Parser\Ini
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @return \phpbrowscap\Formatter\FormatterInterface
     */
    public function getFormatter()
    {
        if ($this->formatter === null) {
            $this->setFormatter(new PhpGetBrowser());
        }

        return $this->formatter;
    }

    /**
     * Gets a cache instance
     *
     * @return \phpbrowscap\Cache\CacheInterface
     */
    public function getCache()
    {
        if ($this->cache === null) {
            $this->cache = new File();
        }
        return $this->cache;
    }

    /**
     * Sets a cache instance
     *
     * @param \phpbrowscap\Cache\CacheInterface $cache
     *
     * @return \phpbrowscap\Parser\Ini
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Gets the version of the Browscap data
     *
     * @return int
     */
    public function getVersion()
    {
        if (self::$version === null) {
            $version = $this->getCache()->get('browscap.version', false);
            if ($version !== null) {
                self::$version = (int)$version;
            }
        }
        return self::$version;
    }

    /**
     * Gets the browser data formatr for the given user agent
     * (or null if no data avaailble, no even the default browser)
     *
     * @param string $user_agent
     * @return FormatterInterface|null
     */
    public function getBrowser($user_agent)
    {
        $formatter = null;

        foreach ($this->getHelper()->getPatterns($user_agent) as $patterns) {
            if (preg_match("/^(?:" . str_replace("\t", ")|(?:", $this->pregQuote($patterns)) . ")$/", $user_agent)) {
                // strtok() requires less memory than explode()
                $pattern = strtok($patterns, "\t");

                while ($pattern !== false) {
                    if (preg_match("/^" . $this->pregQuote($pattern) . "$/", $user_agent)) {
                        $formatter = $this->getFormatter();
                        $formatter->setData($this->getSettings($pattern));
                        break 2;
                    }

                    $pattern = strtok("\t");
                }
            }
        }

        return $formatter;
    }

    /**
     * Gets the content of the source file
     *
     * @return string
     */
    public function getContent()
    {
        return (string)$this->getCache()->get('browscap.ini', true);
    }

    /**
     * Gets the settings for a given pattern (method calls itself to
     * get the data from the parent patterns)
     *
     * @param string $pattern
     * @param array $settings
     * @return array
     */
    protected function getSettings($pattern, $settings = array())
    {
        // set some additional data
        if (count($settings) === 0) {
            $settings['browser_name_regex']   = '/^' . $this->pregQuote($pattern) . '$/';
            $settings['browser_name_pattern'] = $pattern;
        }

        $add_settings = $this->getIniPart($pattern);

        // check if parent pattern set, only keep the first one
        $parent_pattern = null;
        if (isset($add_settings['Parent'])) {
            $parent_pattern = $add_settings['Parent'];
            if (isset($settings['Parent'])) {
                unset($add_settings['Parent']);
            }
        }

        // merge settings
        $settings += $add_settings;

        if ($parent_pattern !== null) {
            return $this->getSettings($parent_pattern, $settings);
        }

        return $settings;
    }

    /**
     * Gets the relevant part (array of settings) of the ini file for a given pattern.
     *
     * @param string $pattern
     * @return array
     */
    protected function getIniPart($pattern)
    {
        $patternhash = md5($pattern);
        $subkey      = $this->getIniPartCacheSubkey($patternhash);

        if (!$this->getCache()->exists('browscap.iniparts.' . $subkey)) {
            $this->createIniParts();
        }

        $return = array();
        $file   = $this->getCache()->getFileName('browscap.iniparts.' . $subkey);
        $handle = fopen($file, "r");
        if ($handle) {
            while (($buffer = fgets($handle)) !== false) {
                if (substr($buffer, 0, 32) === $patternhash) {
                    $return = json_decode(substr($buffer, 32), true);
                    break;
                }
            }
            fclose($handle);
        }

        return $return;
    }

    /**
     * Creates new ini part cache files
     */
    protected function createIniParts()
    {
        // get all patterns from the ini file in the correct order,
        // so that we can calculate with index number of the resulting array,
        // which part to use when the ini file is splitted into its sections.
        preg_match_all('/(?<=\[)(?:[^\r\n]+)(?=\])/m', $this->getContent(), $patternpositions);
        $patternpositions = $patternpositions[0];

        // split the ini file into sections and save the data in one line with a hash of the beloging
        // pattern (filtered in the previous step)
        $ini_parts = preg_split('/\[[^\r\n]+\]/', $this->getContent());
        $contents  = array();
        foreach ($patternpositions as $position => $pattern) {
            $patternhash = md5($pattern);
            $subkey      = $this->getIniPartCacheSubkey($patternhash);
            if (!isset($contents[$subkey])) {
                $contents[$subkey] = '';
            }

            // the position has to be moved by one, because the header of the ini file
            // is also returned as a part
            $contents[$subkey] .= $patternhash . json_encode(
                parse_ini_string($ini_parts[($position + 1)]),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ) . "\n";
        }
        foreach ($contents as $chars => $content) {
            $this->getCache()->set('browscap.iniparts.' . $chars, $content);
        }
    }

    /**
     * Gets the subkey for the ini parts cache file, generated from the given string
     *
     * @param string $string
     * @return string
     */
    protected function getIniPartCacheSubkey($string)
    {
        return $string[0] . $string[1];
    }

    /**
     * Gets a hash from the first charcters of a pattern/user agent, that can be used for a fast comparison,
     * by comparing only the hashes, without having to match the complete pattern against the user agent.
     *
     * @param string $pattern
     * @return string
     */
    protected static function getPatternStart($pattern)
    {
        return md5(preg_replace('/^([^\*\?\s]*)[\*\?\s].*$/', '\\1', substr($pattern, 0, 32)));
    }

    /**
     * Gets the minimum length of the patern (used in the getPatterns() method to
     * check against the user agent length)
     *
     * @param string $pattern
     * @return int
     */
    protected static function getPatternLength($pattern)
    {
        return strlen(str_replace('*', '', $pattern));
    }

    /**
     * Quotes a pattern from the browscap.ini file, so that it can be used in regular expressions
     *
     * @param string $pattern
     * @return string
     */
    protected static function pregQuote($pattern)
    {
        $pattern = preg_quote($pattern, "/");

        // The \\x replacement is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
        // @source https://github.com/browscap/browscap-php
        return str_replace(array('\*', '\?', '\\x'), array('.*', '.', '\\\\x'), $pattern);
    }
}
