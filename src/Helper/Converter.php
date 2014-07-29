<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace phpbrowscap\Helper;

use Symfony\Component\Filesystem\Filesystem;
use phpbrowscap\Exception\FileNotFoundException;
use phpbrowscap\Cache\BrowscapCache;
use phpbrowscap\Parser\Helper\Pattern;
use Psr\Log\LoggerInterface;

class Converter
{
    /** @var string */
    const BROWSCAP_VERSION_KEY = 'GJK_Browscap_Version';

    /**
     * Current cache version
     */
    const CACHE_FILE_VERSION = '2.0b';

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
     * @var \phpbrowscap\Cache\BrowscapCache
     */
    private $cache = null;

    /**
     * Number of pattern to combine for a faster regular expression search.
     *
     * @important The number of patterns that can be processed in one step
     *            is limited by the internal regular expression limits.
     * @var int
     */
    private $joinPatterns = 100;

    /**
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \phpbrowscap\Helper\Converter
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Sets a cache instance
     *
     * @param \phpbrowscap\Cache\BrowscapCache $cache
     *
     * @return \Helper\Converter
     */
    public function setCache(BrowscapCache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param string $iniFile
     * @throws \phpbrowscap\Exception\FileNotFoundException
     */
    public function convertFile($iniFile)
    {
        $fs = new Filesystem();

        if (!$fs->exists($iniFile)) {
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
        $this->logger->info('start creating patterns from the ini data');

        $this->createPatterns($iniString);

        $this->logger->info('finished creating patterns from the ini data');

        $this->logger->info('start creating data from the ini data');

        $this->createIniParts($iniString);

        $this->logger->info('finished creating data from the ini data');
    }
    
    public function getIniVersion($iniString)
    {
        $key = $this->pregQuote(self::BROWSCAP_VERSION_KEY);
        if (preg_match("/\.*[" . $key . "\][^[]*Version=(\d+)\D.*/", $iniString, $matches)) {
            if (isset($matches[1])) {
                self::$version = (int)$matches[1];
                
                $this->cache->setItem('browscap.version', $content, false);
            }
        }
    }

    /**
     * Quotes a pattern from the browscap.ini file, so that it can be used in regular expressions
     *
     * @param string $pattern
     * @return string
     */
    private function pregQuote($pattern)
    {
        $pattern = preg_quote($pattern, '/');

        // The \\x replacement is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
        // @source https://github.com/browscap/browscap-php
        return str_replace(array('\*', '\?', '\\x'), array('.*', '.', '\\\\x'), $pattern);
    }

    /**
     * Creates new ini part cache files
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
        $ini_parts = preg_split('/\[[^\r\n]+\]/', $content);
        $contents  = array();
        foreach ($patternpositions as $position => $pattern) {
            $patternhash = md5($pattern);
            $subkey      = $this->getIniPartCacheSubkey($patternhash);
            if (!isset($contents[$subkey])) {
                $contents[$subkey] = array();
            }

            // the position has to be moved by one, because the header of the ini file
            // is also returned as a part
            $contents[$subkey][] = $patternhash . json_encode(
                parse_ini_string($ini_parts[($position + 1)]),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            );
        }

        unset($patternpositions);

        foreach ($contents as $subkey => $content) {
            $this->cache->setItem('browscap.iniparts.' . $subkey, $content);
        }
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
     * Creates new pattern cache files
     */
    private function createPatterns($content)
    {
        // get all relevant patterns from the INI file
        // - containing "*" or "?"
        // - not containing "*" or "?", but not having a comment
        preg_match_all('/(?<=\[)(?:[^\r\n]*[?*][^\r\n]*)(?=\])|(?<=\[)(?:[^\r\n*?]+)(?=\])(?![^\[]*Comment=)/m', $content, $matches);

        if (empty($matches[0]) || !is_array($matches[0])) {
            return false;
        }

        // build an array to structure the data. this requires some memory, but we need this step to be able to
        // sort the data in the way we need it (see below).
        $data        = array();
        $patternList = array();

        foreach ($matches[0] as $match) {
            // get the first characters for a fast search
            $tmp_start  = $this->getPatternStart($match);
            $tmp_length = $this->getPatternLength($match);

            // special handling of default entry
            if ($tmp_length === 0) {
                $tmp_start = str_repeat('z', 32);
            }

            if (!isset($data[$tmp_start])) {
                $data[$tmp_start] = array();
            }
            if (!isset($data[$tmp_start][$tmp_length])) {
                $data[$tmp_start][$tmp_length] = array();
            }
            $data[$tmp_start][$tmp_length][] = $match;

            $patternList[] = $match;
        }

        unset($matches);

        $this->cache->setItem('browscap.patterns', $patternList, true);

        // write optimized file (grouped by the first character of the has, generated from the pattern
        // start) with multiple patterns joined by tabs. this is to speed up loading of the data (small
        // array with pattern strings instead of an large array with single patterns) and also enables
        // us to search for multiple patterns in one preg_match call for a fast first search
        // (3-10 faster), followed by a detailed search for each single pattern.
        $contents = array();
        foreach ($data as $tmp_start => $tmp_entries) {
            foreach ($tmp_entries as $tmp_length => $tmp_patterns) {
                for ($i = 0, $j = ceil(count($tmp_patterns) / $this->joinPatterns); $i < $j; $i++) {
                    $tmp_joinpatterns = implode("\t", array_slice($tmp_patterns, ($i * $this->joinPatterns), $this->joinPatterns));
                    $tmp_subkey       = Pattern::getPatternCacheSubkey($tmp_start);

                    if (!isset($contents[$tmp_subkey])) {
                        $contents[$tmp_subkey] = array();
                    }

                    $contents[$tmp_subkey][] = $tmp_start . ' ' . $tmp_length . ' ' . $tmp_joinpatterns;
                }
            }
        }

        unset($data);

        foreach ($contents as $subkey => $content) {
            $this->cache->setItem('browscap.patterns.' . $subkey, $content, true);
        }

        return true;
    }

    /**
     * Gets a hash from the first charcters of a pattern/user agent, that can be used for a fast comparison,
     * by comparing only the hashes, without having to match the complete pattern against the user agent.
     *
     * @param string $pattern
     * @return string
     */
    private function getPatternStart($pattern)
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
    private function getPatternLength($pattern)
    {
        return strlen(str_replace('*', '', $pattern));
    }
}
