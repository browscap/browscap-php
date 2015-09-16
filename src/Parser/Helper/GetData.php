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
 * @package    Parser\Helper
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace BrowscapPHP\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Data\PropertyFormatter;
use BrowscapPHP\Data\PropertyHolder;
use Psr\Log\LoggerInterface;
use BrowscapPHP\Helper\Quoter;

/**
 * extracts the data and the data for theses pattern from the ini content, optimized for PHP 5.5+
 *
 * @category   Browscap-PHP
 * @package    Parser\Helper
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class GetData implements GetDataInterface
{
    /**
     * The cache instance
     *
     * @var \BrowscapPHP\Cache\BrowscapCache
     */
    private $cache = null;

    /**
     * a logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * @var \BrowscapPHP\Helper\Quoter
     */
    private $quoter = null;

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
     * @return \BrowscapPHP\Parser\Helper\GetData
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
     * @return \BrowscapPHP\Parser\Helper\GetData
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
     * @param \BrowscapPHP\Helper\Quoter $quoter
     *
     * @return \BrowscapPHP\Parser\Helper\GetData
     */
    public function setQuoter(Quoter $quoter)
    {
        $this->quoter = $quoter;

        return $this;
    }

    /**
     * @return \BrowscapPHP\Helper\Quoter
     */
    public function getQuoter()
    {
        return $this->quoter;
    }

    /**
     * Gets the settings for a given pattern (method calls itself to
     * get the data from the parent patterns)
     *
     * @param  string $pattern
     * @param  array  $settings
     * @return array
     */
    public function getSettings($pattern, array $settings = array())
    {
        // The pattern has been pre-quoted on generation to speed up the pattern search,
        // but for this check we need the unquoted version
        $unquotedPattern = $this->getQuoter()->pregUnQuote($pattern);

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
            return $this->getSettings($this->getQuoter()->pregQuote($parentPattern), $settings);
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
        $patternhash = Pattern::getHashForParts($pattern);
        $subkey      = SubKey::getIniPartCacheSubKey($patternhash);

        if (!$this->getCache()->hasItem('browscap.iniparts.'.$subkey, true)) {
            $this->getLogger()->debug('cache key "browscap.iniparts.'.$subkey.'" not found');

            return array();
        }

        $success = null;
        $file    = $this->getCache()->getItem('browscap.iniparts.'.$subkey, true, $success);

        if (!$success) {
            $this->getLogger()->debug('cache key "browscap.iniparts.'.$subkey.'" not found');

            return array();
        }

        if (!is_array($file) || !count($file)) {
            $this->getLogger()->debug('cache key "browscap.iniparts.'.$subkey.'" was empty');

            return array();
        }

        $propertyHolder    = new PropertyHolder();
        $propertyFormatter = new PropertyFormatter();
        $propertyFormatter->setPropertyHolder($propertyHolder);

        $return = array();
        foreach ($file as $buffer) {
            list($tmpBuffer, $patterns) = explode("\t", $buffer, 2);

            if ($tmpBuffer === $patternhash) {
                $return = json_decode($patterns, true);

                foreach (array_keys($return) as $property) {
                    $return[$property] = $propertyFormatter->formatPropertyValue(
                        $return[$property],
                        $property
                    );
                }

                break;
            }
        }

        return $return;
    }
}
