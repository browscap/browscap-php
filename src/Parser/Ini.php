<?php
namespace phpbrowscap\Parser;

use phpbrowscap\Cache\BrowscapCache;
use phpbrowscap\Formatter\FormatterInterface;
use phpbrowscap\Formatter\PhpGetBrowser;
use phpbrowscap\Helper\Quoter;
use WurflCache\Adapter\NullStorage;
use phpbrowscap\Parser\Helper\GetPatternInterface;

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
class Ini implements ParserInterface
{
    /**
     * The key to search for in the INI file to find the browscap settings
     */
    const BROWSCAP_VERSION_KEY = 'GJK_Browscap_Version';

    /**
     * The cache instance
     *
     * @var \phpbrowscap\Cache\BrowscapCache
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
    public function setHelper(GetPatternInterface $helper)
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
     * @return \phpbrowscap\Cache\BrowscapCache
     */
    public function getCache()
    {
        if ($this->cache === null) {
            $adapter     = new NullStorage();
            $this->cache = new BrowscapCache($adapter);
        }
        
        return $this->cache;
    }

    /**
     * Sets a cache instance
     *
     * @param \phpbrowscap\Cache\BrowscapCache $cache
     *
     * @return \phpbrowscap\Parser\Ini
     */
    public function setCache(BrowscapCache $cache)
    {
        $this->cache = $cache;

        return $this;
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
        $formatter    = null;
        $quoterHelper = new Quoter();

        foreach ($this->getHelper()->getPatterns($user_agent) as $patterns) {
            if (preg_match("/^(?:" . str_replace("\t", ")|(?:", $quoterHelper->pregQuote($patterns)) . ")$/", $user_agent)) {
                // strtok() requires less memory than explode()
                $pattern = strtok($patterns, "\t");

                while ($pattern !== false) {
                    if (preg_match("/^" . $quoterHelper->pregQuote($pattern, '/') . "$/", $user_agent)) {
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
        $success = null;
        $content = (string)$this->getCache()->getItem('browscap.ini', true, $success);

        if (!$success) {
            return '';
        }

        return $content;
    }

    /**
     * Gets the settings for a given pattern (method calls itself to
     * get the data from the parent patterns)
     *
     * @param string $pattern
     * @param array $settings
     * @return array
     */
    private function getSettings($pattern, array $settings = array())
    {
        // set some additional data
        if (count($settings) === 0) {
            $quoterHelper = new Quoter();

            $settings['browser_name_regex']   = '/^' . $quoterHelper->pregQuote($pattern) . '$/';
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
    private function getIniPart($pattern)
    {
        $patternhash = md5($pattern);
        $subkey      = $this->getIniPartCacheSubkey($patternhash);

        if (!$this->getCache()->hasItem('browscap.iniparts.' . $subkey, true)) {
            return array();
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
     * Gets the subkey for the ini parts cache file, generated from the given string
     *
     * @param string $string
     * @return string
     */
    private function getIniPartCacheSubkey($string)
    {
        return $string[0] . $string[1];
    }
}
