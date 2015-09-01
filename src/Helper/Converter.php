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
 * @package    Helper
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace BrowscapPHP\Helper;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Data\PropertyHolder;
use BrowscapPHP\Exception\FileNotFoundException;
use BrowscapPHP\Parser\Helper\Pattern;
use BrowscapPHP\Parser\Helper\SubKey;
use BrowscapPHP\Parser\Ini;
use Psr\Log\LoggerInterface;

/**
 * helper to convert the ini data, parses the data and stores them into the cache
 *
 * @category   Browscap-PHP
 * @package    Helper
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
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
    const REGEX_DELIMITER = '@';
    const REGEX_MODIFIERS = 'i';
    const COMPRESSION_PATTERN_START = '@';
    const COMPRESSION_PATTERN_DELIMITER = '|';

    /** @var \Psr\Log\LoggerInterface */
    private $logger = null;

    /**
     * The cache instance
     *
     * @var \BrowscapPHP\Cache\BrowscapCache
     */
    private $cache = null;

    /**
     * a filesystem helper instance
     *
     * @var \BrowscapPHP\Helper\Filesystem
     */
    private $filessystem = null;

    /**
     * Number of pattern to combine for a faster regular expression search.
     *
     * @important The number of patterns that can be processed in one step
     *            is limited by the internal regular expression limits.
     * @var int
     */
    private $joinPatterns = 100;

    /**
     * version of the ini file
     *
     * @var integer
     */
    private $iniVersion = 0;

    /**
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \BrowscapPHP\Helper\Converter
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
     * Sets a cache instance
     *
     * @param \BrowscapPHP\Cache\BrowscapCache $cache
     *
     * @return \BrowscapPHP\Helper\Converter
     */
    public function setCache(BrowscapCache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Returns a cache instance
     *
     * @return \BrowscapPHP\Cache\BrowscapCache $cache
     */
    public function getCache()
    {
        return $this->cache;
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

        $this->getLogger()->info('start reading file');

        $iniString = file_get_contents($iniFile);

        $this->getLogger()->info('finished reading file');

        $this->convertString($iniString);
    }

    /**
     * @param string $iniString
     */
    public function convertString($iniString)
    {
        $this->getLogger()->info('start creating patterns from the ini data');

        $this->createPatterns($iniString);

        $this->getLogger()->info('finished creating patterns from the ini data');

        $this->getLogger()->info('start creating data from the ini data');

        $this->createIniParts($iniString);

        $this->getLogger()->info('finished creating data from the ini data');
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
        $key          = $quoterHelper->pregQuote(Ini::BROWSCAP_VERSION_KEY);

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
        $this->getCache()->setItem('browscap.version', $this->iniVersion, false);

        return $this;
    }

    /**
     * Creates new ini part cache files
     * @param string $content
     */
    private function createIniParts($content)
    {
        // get all patterns from the ini file in the correct order,
        // so that we can calculate with index number of the resulting array,
        // which part to use when the ini file is splitted into its sections.
        preg_match_all('/(?<=\[)(?:[^\r\n]+)(?=\])/m', $content, $patternpositions);
        $patternpositions = $patternpositions[0];

        // split the ini file into sections and save the data in one line with a hash of the beloging
        // pattern (filtered in the previous step)
        $iniParts = preg_split('/\[[^\r\n]+\]/', $content);
        $contents = array();
        foreach ($patternpositions as $position => $pattern) {
            $pattern     = strtolower($pattern);
            $patternhash = md5($pattern);
            $subkey      = SubKey::getPatternCacheSubkey($patternhash);

            if (!isset($contents[$subkey])) {
                $contents[$subkey] = array();
            }

            $browserProperties = parse_ini_string($iniParts[($position + 1)]);

            foreach (array_keys($browserProperties) as $property) {
                $browserProperties[$property] = $this->formatPropertyValue(
                    $browserProperties[$property],
                    $property
                );
            }
            // the position has to be moved by one, because the header of the ini file
            // is also returned as a part
            $contents[$subkey][] = $patternhash.json_encode(
                $browserProperties,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            );
        }

        unset($patternpositions);
        unset($iniParts);

        $subkeys = array_flip(SubKey::getAllIniPartCacheSubKeys());
        foreach ($contents as $subkey => $content) {
            $subkey = (string) $subkey;
            $this->getCache()->setItem('browscap.iniparts.' . $subkey, $content, true);
            unset($subkeys[$subkey]);
        }

        foreach (array_keys($subkeys) as $subkey) {
            $this->getCache()->setItem('browscap.iniparts.' . $subkey, array(), true);
        }
    }

    /**
     * Creates new pattern cache files
     *
     * @param string $content
     *
     * @return bool
     */
    private function createPatterns($content)
    {
        // get all relevant patterns from the INI file
        // - containing "*" or "?"
        // - not containing "*" or "?", but not having a comment
        preg_match_all(
            '/(?<=\[)(?:[^\r\n]*[?*][^\r\n]*)(?=\])|(?<=\[)(?:[^\r\n*?]+)(?=\])(?![^\[]*Comment=)/m',
            $content,
            $matches
        );

        if (empty($matches[0]) || !is_array($matches[0])) {
            return false;
        }

        // build an array to structure the data. this requires some memory, but we need this step to be able to
        // sort the data in the way we need it (see below).
        $data = array();

        foreach ($matches[0] as $match) {
            $match = strtolower($match);

            // get the first characters for a fast search
            $tmpStart  = Pattern::getPatternStart($match);
            $tmpLength = Pattern::getPatternLength($match);

            // special handling of default entry
            if ($tmpLength === 0) {
                $tmpStart = str_repeat('z', 32);
            }

            if (!isset($data[$tmpStart])) {
                $data[$tmpStart] = array();
            }

            $quoterHelper = new Quoter();
            $match        = $quoterHelper->pregQuote($match);

            // Check if the pattern contains digits - in this case we replace them with a digit regular expression,
            // so that very similar patterns (e.g. only with different browser version numbers) can be compressed.
            // This helps to speed up the first (and most expensive) part of the pattern search a lot.
            if (strpbrk($match, '0123456789') !== false) {
                $compressedPattern = preg_replace('/\d/', '[\d]', $match);
                if (!in_array($compressedPattern, $data[$tmpStart])) {
                    $data[$tmpStart][] = $compressedPattern;
                }
            } else {
                $data[$tmpStart][] = $match;
            }
            //$data[$tmpStart][] = $match;
        }

        unset($matches);

        // write optimized file (grouped by the first character of the has, generated from the pattern
        // start) with multiple patterns joined by tabs. this is to speed up loading of the data (small
        // array with pattern strings instead of an large array with single patterns) and also enables
        // us to search for multiple patterns in one preg_match call for a fast first search
        // (3-10 faster), followed by a detailed search for each single pattern.
        $contents = array();
        foreach ($data as $tmpStart => $tmpPatterns) {
            for ($i = 0, $j = ceil(count($tmpPatterns) / $this->joinPatterns); $i < $j; $i++) {
                $tmpJoinPatterns = implode(
                    "\t",
                    array_slice($tmpPatterns, ($i * $this->joinPatterns), $this->joinPatterns)
                );

                $tmpSubkey = SubKey::getPatternCacheSubkey($tmpStart);

                if (!isset($contents[$tmpSubkey])) {
                    $contents[$tmpSubkey] = array();
                }

                $contents[$tmpSubkey][] = $tmpStart.' '.$tmpJoinPatterns;
            }
        }

        unset($data);

        // write cache files. important: also write empty cache files for
        // unused patterns, so that the regeneration is not unnecessarily
        // triggered by the getPatterns() method.
        $subkeys = array_flip(SubKey::getAllPatternCacheSubkeys());
        foreach ($contents as $subkey => $content) {
            $subkey = (string) $subkey;
            $this->cache->setItem('browscap.patterns.' . $subkey, $content, true);
            unset($subkeys[$subkey]);
        }

        foreach (array_keys($subkeys) as $subkey) {
            $this->getCache()->setItem('browscap.patterns.' . $subkey, array(), true);
        }

        return true;
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
                    $valueOutput = 'true';
                } elseif (false === $value || $value === 'false' || $value === '') {
                    $valueOutput = 'false';
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
