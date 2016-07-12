<?php
/**
 * Copyright (c) 1998-2015 Browser Capabilities Project
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
 * @copyright  1998-2015 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace BrowscapPHP\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Data\PropertyFormatter;
use BrowscapPHP\Data\PropertyHolder;
use BrowscapPHP\Helper\Quoter;
use Psr\Log\LoggerInterface;

/**
 * extracts the data and the data for theses pattern from the ini content, optimized for PHP 5.5+
 *
 * @category   Browscap-PHP
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class GetData implements GetDataInterface
{
    /**
     * The cache instance
     *
     * @var \BrowscapPHP\Cache\BrowscapCacheInterface
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
     * class contsructor
     *
     * @param \BrowscapPHP\Cache\BrowscapCacheInterface $cache
     * @param \Psr\Log\LoggerInterface                  $logger
     * @param \BrowscapPHP\Helper\Quoter                $quoter
     */
    public function __construct(BrowscapCacheInterface $cache, LoggerInterface $logger, Quoter $quoter)
    {
        $this->cache  = $cache;
        $this->logger = $logger;
        $this->quoter = $quoter;
    }

    /**
     * Gets the settings for a given pattern (method calls itself to
     * get the data from the parent patterns)
     *
     * @param  string $pattern
     * @param  array  $settings
     * @return array
     */
    public function getSettings($pattern, array $settings = [])
    {
        // The pattern has been pre-quoted on generation to speed up the pattern search,
        // but for this check we need the unquoted version
        $unquotedPattern = $this->quoter->pregUnQuote($pattern);

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
            return $this->getSettings($this->quoter->pregQuote($parentPattern), $settings);
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

        if (!$this->cache->hasItem('browscap.iniparts.' . $subkey, true)) {
            $this->logger->debug('cache key "browscap.iniparts.' . $subkey . '" not found');

            return [];
        }

        $success = null;
        $file    = $this->cache->getItem('browscap.iniparts.' . $subkey, true, $success);

        if (!$success) {
            $this->logger->debug('cache key "browscap.iniparts.' . $subkey . '" not found');

            return [];
        }

        if (!is_array($file) || !count($file)) {
            $this->logger->debug('cache key "browscap.iniparts.' . $subkey . '" was empty');

            return [];
        }

        $propertyFormatter = new PropertyFormatter(new PropertyHolder());
        $return            = [];

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
