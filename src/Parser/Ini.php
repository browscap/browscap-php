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
 * @package    Parser
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Parser;

use phpbrowscap\Cache\BrowscapCache;
use phpbrowscap\Formatter\FormatterInterface;
use phpbrowscap\Helper\Quoter;
use phpbrowscap\Parser\Helper\GetPatternInterface;
use phpbrowscap\Data\PropertyHolder;
use Psr\Log\LoggerInterface;

/**
 * Ini parser class (compatible with PHP 5.3+)
 *
 * @category   Browscap-PHP
 * @package    Parser
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
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

    /** @var \Psr\Log\LoggerInterface */
    private $logger = null;

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
     * Set the formatter instance to use for the getBrowser() result
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
        return $this->formatter;
    }

    /**
     * Gets a cache instance
     *
     * @return \phpbrowscap\Cache\BrowscapCache
     */
    public function getCache()
    {
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
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \phpbrowscap\Parser\Ini
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Returns a logger instance
     *
     * @return \Psr\Log\LoggerInterface $logger
     */
    public function getLogger()
    {
        return $this->logger;
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
            if (preg_match('/^(?:' . str_replace("\t", ')|(?:', $quoterHelper->pregQuote($patterns)) . ')$/i', $user_agent)) {
                // strtok() requires less memory than explode()
                $pattern = strtok($patterns, "\t");

                while ($pattern !== false) {
                    $quotedPattern = '/^' . $quoterHelper->pregQuote($pattern, '/') . '$/i';

                    if (preg_match($quotedPattern, $user_agent)) {
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

        $success = null;
        $file    = $this->getCache()->getItem('browscap.iniparts.' . $subkey, true, $success);

        if (!$success) {
            return array();
        }

        $return = array();
        foreach ($file as $buffer) {
            if (substr($buffer, 0, 32) === $patternhash) {
                $return = json_decode(substr($buffer, 32), true);

                foreach (array_keys($return) as $property) {
                    $return[$property] = $this->formatPropertyValue(
                        $return[$property],
                        $property
                    );
                }

                break;
            }
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

    /**
     * formats the name of a property
     *
     * @param string $value
     * @param string $property
     *
     * @return string
     */
    private function formatPropertyValue($value, $property)
    {
        $valueOutput    = $value;
        $propertyHolder = new PropertyHolder();

        switch ($propertyHolder->getPropertyType($property)) {
            case PropertyHolder::TYPE_BOOLEAN:
                if (true === $value || $value === 'true' || $value === '1') {
                    $valueOutput = true;
                } elseif (false === $value || $value === 'false' || $value === '') {
                    $valueOutput = false;
                } else {
                    $valueOutput = '';
                }
                break;
            case PropertyHolder::TYPE_IN_ARRAY:
                try {
                    $valueOutput = $propertyHolder->checkValueInArray($property, $value);
                } catch (\InvalidArgumentException $ex) {
                    $valueOutput = '';
                }
                break;
            default:
                // nothing t do here
                break;
        }

        return $valueOutput;
    }
}
