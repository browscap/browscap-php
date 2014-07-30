<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Refer to the LICENSE file distributed with this package.
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
use phpbrowscap\Formatter\PhpGetBrowser;
use phpbrowscap\Helper\Quoter;
use WurflCache\Adapter\NullStorage;
use phpbrowscap\Parser\Helper\GetPatternInterface;
use Psr\Log\LoggerInterface;

/**
 * Ini parser class (compatible with PHP 5.3+)
 *
 * @category   Browscap-PHP
 * @package    Parser
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
            if (preg_match('/^(?:' . str_replace("\t", ')|(?:', $quoterHelper->pregQuote($patterns)) . ')$/', $user_agent)) {
                // strtok() requires less memory than explode()
                $pattern = strtok($patterns, "\t");

                while ($pattern !== false) {
                    $this->logger->debug('1:' . $pattern);
                    $this->logger->debug('2:' . '/^' . $quoterHelper->pregQuote($pattern, '/') . '$/');
                    
                    if (preg_match('/^' . $quoterHelper->pregQuote($pattern, '/') . '$/', $user_agent)) {
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

        $return = array();
        $file   = $this->getCache()->getItem('browscap.iniparts.' . $subkey);
        
        foreach ($file as $buffer) {
            if (substr($buffer, 0, 32) === $patternhash) {
                $return = json_decode(substr($buffer, 32), true);
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
}
