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

namespace BrowscapPHP\Parser;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Data\PropertyHolder;
use BrowscapPHP\Formatter\FormatterInterface;
use BrowscapPHP\Helper\Converter;
use BrowscapPHP\Helper\Quoter;
use BrowscapPHP\Parser\Helper\GetPatternInterface;
use BrowscapPHP\Parser\Helper\Pattern;
use BrowscapPHP\Parser\Helper\SubKey;
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
     * @var \BrowscapPHP\Cache\BrowscapCache
     */
    private $cache = null;

    /**
     * @var Helper\GetPatternInterface
     */
    private $helper = null;

    /**
     * Formatter to use
     *
     * @var \BrowscapPHP\Formatter\FormatterInterface
     */
    private $formatter = null;

    /** @var \Psr\Log\LoggerInterface */
    private $logger = null;

    /**
     * @param \BrowscapPHP\Parser\Helper\GetPatternInterface $helper
     *
     * @return \BrowscapPHP\Parser\Ini
     */
    public function setHelper(GetPatternInterface $helper)
    {
        $this->helper = $helper;

        return $this;
    }

    /**
     * @return \BrowscapPHP\Parser\Helper\GetPatternInterface
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Set the formatter instance to use for the getBrowser() result
     *
     * @param \BrowscapPHP\Formatter\FormatterInterface $formatter
     *
     * @return \BrowscapPHP\Parser\Ini
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @return \BrowscapPHP\Formatter\FormatterInterface
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Gets a cache instance
     *
     * @return \BrowscapPHP\Cache\BrowscapCache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Sets a cache instance
     *
     * @param \BrowscapPHP\Cache\BrowscapCache $cache
     *
     * @return \BrowscapPHP\Parser\Ini
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
     * @return \BrowscapPHP\Parser\Ini
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
     * @param  string                  $userAgent
     * @return FormatterInterface|null
     */
    public function getBrowser($userAgent)
    {
        $userAgent = strtolower($userAgent);
        $formatter = null;
        $starts = Pattern::getPatternStart($userAgent, true);

        // add special key to fall back to the default browser
        $starts[] = str_repeat('z', 32);

        foreach ($this->getHelper()->getPatterns($userAgent) as $patterns) {
            $usedMatch = '/^(?:'.str_replace("\t", ')|(', $patterns).')$/i';
            $result = preg_match(
                $usedMatch,
                $userAgent
            );

            if (!$result) {
                continue;
            }

            // strtok() requires less memory than explode()
            $pattern = strtok($patterns, "\t");

            while ($pattern !== false) {
                $pattern       = str_replace('[\d]', '(\d)', $pattern);
                $quotedPattern = '/^' . $pattern . '$/i';

                if (preg_match($quotedPattern, $userAgent, $matches)) {
                    // Insert the digits back into the pattern, so that we can search the settings for it
                    if (count($matches) > 1) {
                        array_shift($matches);
                        foreach ($matches as $one_match) {
                            $numPos  = strpos($pattern, '(\d)');
                            $pattern = substr_replace($pattern, $one_match, $numPos, 4);
                        }
                    }

                    // Try to get settings - as digits have been replaced to speed up the pattern search (up to 90 faster),
                    // we won't always find the data in the first step - so check if settings have been found and if not,
                    // search for the next pattern.
                    $settings = $this->getSettings($pattern);

                    if (count($settings) > 0) {
                        $formatter = $this->getFormatter();
                        $formatter->setData($settings);
                        break 2;
                    }
                }

                $pattern = strtok("\t");
            }
        }

        return $formatter;
    }

    /**
     * Gets the settings for a given pattern (method calls itself to
     * get the data from the parent patterns)
     *
     * @param  string $pattern
     * @param  array  $settings
     * @return array
     */
    private function getSettings($pattern, array $settings = array())
    {
        $quoterHelper = new Quoter();

        // The pattern has been pre-quoted on generation to speed up the pattern search,
        // but for this check we need the unquoted version
        $unquotedPattern = $quoterHelper->pregUnQuote($pattern);

        // Try to get settings for the pattern
        $addedSettings = $this->getIniPart($unquotedPattern);

        // set some additional data
        if (count($settings) === 0) {
            // The optimization with replaced digits get can now result in setting searches, for which we
            // won't find a result - so only add the pattern information, is settings have been found.
            //
            // If not an empty array will be returned and the calling function can easily check if a pattern
            // has been found.
            if (count($addedSettings) > 0) {
                $settings['browser_name_regex']   = '/^' . $pattern . '$/';
                $settings['browser_name_pattern'] = $unquotedPattern;
            }
        }

        // check if parent pattern set, only keep the first one
        $parentPattern = null;
        if (isset($addedSettings['Parent'])) {
            $parentPattern = $addedSettings['Parent'];

            if (isset($settings['Parent'])) {
                unset($addedSettings['Parent']);
            }
        }

        // merge settings
        $settings += $addedSettings;

        if ($parentPattern !== null) {
            return $this->getSettings($quoterHelper->pregQuote($parentPattern), $settings);
        }

        return $settings;
    }

    /**
     * Gets the relevant part (array of settings) of the ini file for a given pattern.
     *
     * @param  string $pattern
     * @return array
     */
    private function getIniPart($pattern)
    {
        $pattern     = strtolower($pattern);
        $patternhash = md5($pattern);

        if (!$this->getCache()->hasItem('browscap.iniparts.'.$patternhash, true)) {
            return array();
        }

        $success = null;
        $file    = $this->getCache()->getItem('browscap.iniparts.'.$patternhash, true, $success);

        if (!$success) {
            return array();
        }

        $return = array();
        foreach ($file as $buffer) {
            $return = json_decode($buffer, true);

            foreach (array_keys($return) as $property) {
                $return[$property] = $this->formatPropertyValue(
                    $return[$property],
                    $property
                );
            }

            break;
        }

        return $return;
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
