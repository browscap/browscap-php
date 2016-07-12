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

namespace BrowscapPHP\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception\FileNotFoundException;
use BrowscapPHP\IniParser\IniParser;
use Psr\Log\LoggerInterface;

/**
 * patternHelper to convert the ini data, parses the data and stores them into the cache
 *
 * @category   Browscap-PHP
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class Converter
{
    /**
     * Options for regex patterns.
     *
     * REGEX_DELIMITER: Delimiter of all the regex patterns in the whole class.
     * REGEX_MODIFIERS: Regex modifiers.
     */
    const REGEX_DELIMITER               = '@';
    const REGEX_MODIFIERS               = 'i';
    const COMPRESSION_PATTERN_START     = '@';
    const COMPRESSION_PATTERN_DELIMITER = '|';

    /**
     * The key to search for in the INI file to find the browscap settings
     */
    const BROWSCAP_VERSION_KEY = 'GJK_Browscap_Version';

    /** @var \Psr\Log\LoggerInterface */
    private $logger = null;

    /**
     * The cache instance
     *
     * @var \BrowscapPHP\Cache\BrowscapCacheInterface
     */
    private $cache = null;

    /**
     * a filesystem patternHelper instance
     *
     * @var \BrowscapPHP\Helper\Filesystem
     */
    private $filessystem = null;

    /**
     * version of the ini file
     *
     * @var int
     */
    private $iniVersion = 0;

    /**
     * class constructor
     *
     * @param \Psr\Log\LoggerInterface                  $logger
     * @param \BrowscapPHP\Cache\BrowscapCacheInterface $cache
     */
    public function __construct(LoggerInterface $logger, BrowscapCacheInterface $cache)
    {
        $this->logger = $logger;
        $this->cache  = $cache;
    }

    /**
     * Sets a filesystem instance
     *
     * @param \BrowscapPHP\Helper\Filesystem $file
     *
     * @return \BrowscapPHP\Helper\Converter
     */
    public function setFilesystem(Filesystem $file)
    {
        $this->filessystem = $file;

        return $this;
    }

    /**
     * Returns a filesystem instance
     *
     * @return \BrowscapPHP\Helper\Filesystem
     */
    public function getFilesystem()
    {
        if (null === $this->filessystem) {
            $this->filessystem = new Filesystem();
        }

        return $this->filessystem;
    }

    /**
     * @param  string                                       $iniFile
     * @throws \BrowscapPHP\Exception\FileNotFoundException
     */
    public function convertFile($iniFile)
    {
        if (!$this->getFilesystem()->exists($iniFile)) {
            throw FileNotFoundException::fileNotFound($iniFile);
        }

        $this->logger->info('start reading file');

        $iniString = file_get_contents($iniFile);

        $this->logger->info('finished reading file');

        $this->convertString($iniString);
    }

    /**
     * @param string $iniString
     */
    public function convertString($iniString)
    {
        $iniParser = new IniParser();

        $this->logger->info('start creating patterns from the ini data');

        foreach ($iniParser->createPatterns($iniString) as $patternsHashList) {
            foreach ($patternsHashList as $subkey => $content) {
                if (!$this->cache->setItem('browscap.patterns.' . $subkey, $content, true)) {
                    $this->logger->error('could not write pattern data "' . $subkey . '" to the cache');
                }
            }
        }

        $this->logger->info('finished creating patterns from the ini data');

        $this->logger->info('start creating data from the ini data');

        foreach ($iniParser->createIniParts($iniString) as $patternsContentList) {
            foreach ($patternsContentList as $subkey => $content) {
                if (!$this->cache->setItem('browscap.iniparts.' . $subkey, $content, true)) {
                    $this->logger->error('could not write property data "' . $subkey . '" to the cache');
                }
            }
        }

        $this->cache->setItem('browscap.releaseDate', $this->getIniReleaseDate($iniString), false);
        $this->cache->setItem('browscap.type', $this->getIniType($iniString), false);

        $this->logger->info('finished creating data from the ini data');
    }

    /**
     * Parses the ini data to get the version of loaded ini file
     *
     * @param string $iniString The loaded ini data
     *
     * @return int
     */
    public function getIniVersion($iniString)
    {
        $quoterHelper = new Quoter();
        $key          = $quoterHelper->pregQuote(self::BROWSCAP_VERSION_KEY);

        if (preg_match('/\.*\[' . $key . '\][^\[]*Version=(\d+)\D.*/', $iniString, $matches)) {
            if (isset($matches[1])) {
                $this->iniVersion = (int) $matches[1];
            }
        }

        return $this->iniVersion;
    }

    /**
     * sets the version
     *
     * @param int $version
     *
     * @return \BrowscapPHP\Helper\Converter
     */
    public function setVersion($version)
    {
        $this->iniVersion = $version;

        return $this;
    }

    /**
     * stores the version of the ini file into cache
     *
     * @return \BrowscapPHP\Helper\Converter
     */
    public function storeVersion()
    {
        $this->cache->setItem('browscap.version', $this->iniVersion, false);

        return $this;
    }

    /**
     * Parses the ini data to get the releaseDate of loaded ini file
     *
     * @param string $iniString The loaded ini data
     *
     * @return string|null
     */
    private function getIniReleaseDate($iniString)
    {
        if (preg_match('/Released=(.*)/', $iniString, $matches)) {
            if (isset($matches[1])) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Parses the ini data to get the releaseDate of loaded ini file
     *
     * @param string $iniString The loaded ini data
     *
     * @return string|null
     */
    private function getIniType($iniString)
    {
        if (preg_match('/Type=(.*)/', $iniString, $matches)) {
            if (isset($matches[1])) {
                return $matches[1];
            }
        }

        return null;
    }
}
