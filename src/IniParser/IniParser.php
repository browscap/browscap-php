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

namespace BrowscapPHP\IniParser;

use BrowscapPHP\Data\PropertyFormatter;
use BrowscapPHP\Data\PropertyHolder;
use BrowscapPHP\Helper\Quoter;
use BrowscapPHP\Parser\Helper\Pattern;
use BrowscapPHP\Parser\Helper\SubKey;

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
class IniParser
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
     * Number of pattern to combine for a faster regular expression search.
     *
     * @important The number of patterns that can be processed in one step
     *            is limited by the internal regular expression limits.
     * @var int
     */
    CONST COUNT_PATTERN = 50;

    /**
     * Creates new ini part cache files
     *
     * @param string $content
     *
     * @return \Generator
     */
    public function createIniParts($content)
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

        $propertyHolder    = new PropertyHolder();
        $propertyFormatter = new PropertyFormatter();
        $propertyFormatter->setPropertyHolder($propertyHolder);

        foreach ($patternpositions as $position => $pattern) {
            //var_dump(__CLASS__ . '::' . __FUNCTION__, $pattern);
            $pattern     = strtolower($pattern);
            $patternhash = Pattern::getHashForParts($pattern);
            $subkey      = SubKey::getIniPartCacheSubKey($patternhash);
            var_dump(__CLASS__ . '::' . __FUNCTION__, $pattern, $patternhash, $subkey);

            if (!isset($contents[$subkey])) {
                $contents[$subkey] = array();
            }

            $browserProperties = parse_ini_string($iniParts[($position + 1)], INI_SCANNER_RAW);

            foreach (array_keys($browserProperties) as $property) {
                $browserProperties[$property] = $propertyFormatter->formatPropertyValue(
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
            //var_dump(__CLASS__ . '::' . __FUNCTION__, $subkey, $content);
            yield array($subkey => $content);

            unset($subkeys[$subkey]);
        }

        foreach (array_keys($subkeys) as $subkey) {
            $subkey = (string) $subkey;

            yield array($subkey => array());
        }
    }

    /**
     * Creates new pattern cache files
     *
     * @param string $content
     *
     * @return \Generator
     */
    public function createPatterns($content)
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
            yield array();
            return;
        }

        $quoterHelper = new Quoter();

        // build an array to structure the data. this requires some memory, but we need this step to be able to
        // sort the data in the way we need it (see below).
        $data = array();

        foreach ($matches[0] as $pattern) {
            if ('GJK_Browscap_Version' === $pattern) {
                continue;
            }

            $pattern     = strtolower($pattern);
            $patternhash = Pattern::getHashForPattern($pattern, false);
            $tmpLength   = Pattern::getPatternLength($pattern);

            // special handling of default entry
            if ($tmpLength === 0) {
                $patternhash = str_repeat('z', 32);
            }

            if (!isset($data[$patternhash])) {
                $data[$patternhash] = array();
            }

            $pattern = $quoterHelper->pregQuote($pattern);

            // Check if the pattern contains digits - in this case we replace them with a digit regular expression,
            // so that very similar patterns (e.g. only with different browser version numbers) can be compressed.
            // This helps to speed up the first (and most expensive) part of the pattern search a lot.
            if (strpbrk($pattern, '0123456789') !== false) {
                $compressedPattern = preg_replace('/\d/', '[\d]', $pattern);

                if (!in_array($compressedPattern, $data[$patternhash])) {
                    $data[$patternhash][] = $compressedPattern;
                }
            } else {
                $data[$patternhash][] = $pattern;
            }
        }

        unset($matches);

        // write optimized file (grouped by the first character of the has, generated from the pattern
        // start) with multiple patterns joined by tabs. this is to speed up loading of the data (small
        // array with pattern strings instead of an large array with single patterns) and also enables
        // us to search for multiple patterns in one preg_match call for a fast first search
        // (3-10 faster), followed by a detailed search for each single pattern.
        $contents = array();
        foreach ($data as $patternhash => $tmpPatterns) {
            if (empty($tmpPatterns)) {
                continue;
            }

            $subkey = SubKey::getPatternCacheSubkey($patternhash);
            var_dump(__CLASS__ . '::' . __FUNCTION__, $patternhash, $subkey);
            if (!isset($contents[$subkey])) {
                $contents[$subkey] = array();
            }

            for ($i = 0, $j = ceil(count($tmpPatterns) / self::COUNT_PATTERN); $i < $j; $i++) {
                $tmpJoinPatterns = implode(
                    "\t",
                    array_slice($tmpPatterns, ($i * self::COUNT_PATTERN), self::COUNT_PATTERN)
                );

                $contents[$subkey][] = $patternhash.' '.$tmpJoinPatterns;
                var_dump(__CLASS__ . '::' . __FUNCTION__, $subkey, $patternhash.' '.$tmpJoinPatterns);
            }
        }

        unset($data);

        $subkeys = SubKey::getAllPatternCacheSubkeys();
        foreach ($contents as $subkey => $content) {
            $subkey = (string) $subkey;
            var_dump(__CLASS__ . '::' . __FUNCTION__, $subkey, $content);
            yield array($subkey => $content);

            unset($subkeys[$subkey]);
        }

        foreach (array_keys($subkeys) as $subkey) {
            $subkey = (string) $subkey;

            yield array($subkey => array());
        }
    }

    /**
     * creates the cache content
     *
     * @param string $iniContent The content of the downloaded ini file
     *
     * @return array
     * @throws \UnexpectedValueException
     */
    public function createCacheNewWay($iniContent)
    {
        $matches          = array();
        $patternPositions = array();

        // get all patterns from the ini file in the correct order,
        // so that we can calculate with index number of the resulting array,
        // which part to use when the ini file is split into its sections.
        preg_match_all('/(?<=\[)(?:[^\r\n]+)(?=\])/m', $iniContent, $patternPositions);

        if (!isset($patternPositions[0])) {
            throw new \UnexpectedValueException('could not extract patterns from ini file');
        }

        $patternPositions = $patternPositions[0];

        if (!count($patternPositions)) {
            throw new \UnexpectedValueException('no patterns were found inside the ini file');
        }

        // split the ini file into sections and save the data in one line with a hash of the belonging
        // pattern (filtered in the previous step)
        $iniParts    = preg_split('/\[[^\r\n]+\]/', $iniContent);
        $tmpPatterns = array();
        $browsers    = array();

        $quoterHelper = new Quoter();

        foreach ($patternPositions as $position => $userAgent) {
            if ('GJK_Browscap_Version' === $userAgent) {
                continue;
            }

            $properties = parse_ini_string($iniParts[($position + 1)], true, INI_SCANNER_RAW);

            if (empty($properties['Comment'])
                || false !== strpos($userAgent, '*')
                || false !== strpos($userAgent, '?')
            ) {
                $pattern      = $quoterHelper->pregQuote($userAgent, self::REGEX_DELIMITER);
                $matchesCount = preg_match_all('@\d@', $pattern, $matches);

                if (!$matchesCount) {
                    $tmpPatterns[$pattern] = $userAgent;
                } else {
                    $compressedPattern = preg_replace('@\d@', '(\d)', $pattern);

                    if (!isset($tmpPatterns[$compressedPattern])) {
                        $tmpPatterns[$compressedPattern] = array('first' => $pattern);
                    }

                    $tmpPatterns[$compressedPattern][$userAgent] = $matches[0];
                }
            }

            $browsers[$userAgent] = $properties;

            unset($position, $userAgent);
        }

        $patterns = $this->deduplicatePattern($tmpPatterns);
        $data     = array();

        foreach (array_keys($patterns) as $pattern) {
            $patternhash = Pattern::getHashForPattern($pattern, false);
            $tmpLength   = Pattern::getPatternLength($pattern);

            // special handling of default entry
            if ($tmpLength === 0) {
                $patternhash = str_repeat('z', 32);
            }

            if (!isset($data[$patternhash])) {
                $data[$patternhash] = array();
            }

            $data[$patternhash][] = $pattern;
        }

        // write optimized file (grouped by the first character of the has, generated from the pattern
        // start) with multiple patterns joined by tabs. this is to speed up loading of the data (small
        // array with pattern strings instead of an large array with single patterns) and also enables
        // us to search for multiple patterns in one preg_match call for a fast first search
        // (3-10 faster), followed by a detailed search for each single pattern.
        $contents = array();
        foreach ($data as $patternhash => $tmpPatterns) {
            if (empty($tmpPatterns)) {
                continue;
            }

            $subkey = SubKey::getPatternCacheSubkey($patternhash);
            if (!isset($contents[$subkey])) {
                $contents[$subkey] = array();
            }

            for ($i = 0, $j = ceil(count($tmpPatterns) / self::COUNT_PATTERN); $i < $j; $i++) {
                $tmpJoinPatterns = implode(
                    "\t",
                    array_slice($tmpPatterns, ($i * self::COUNT_PATTERN), self::COUNT_PATTERN)
                );

                $contents[$subkey][] = $patternhash.' '.$tmpJoinPatterns;
            }
        }

        unset($data);
        return array($patterns, $browsers, $contents);
    }

    /**
     * @param array $tmpPatterns
     *
     * @return array
     */
    private function deduplicatePattern(array $tmpPatterns)
    {
        $patternList = array();

        foreach ($tmpPatterns as $pattern => $patternData) {
            if (is_string($patternData)) {
                $data = $patternData;
            } elseif (2 == count($patternData)) {
                end($patternData);

                $pattern = $patternData['first'];
                $data    = key($patternData);
            } else {
                unset($patternData['first']);

                $data = $this->deduplicateCompressionPattern($patternData, $pattern);
            }

            $patternList[$pattern] = $data;
        }

        return $patternList;
    }

    /**
     * That looks complicated...
     * All numbers are taken out into $matches, so we check if any of those numbers are identical
     * in all the $matches and if they are we restore them to the $pattern, removing from the $matches.
     * This gives us patterns with "(\d)" only in places that differ for some matches.
     *
     * @param array  $matches
     * @param string $pattern
     *
     * @return array of $matches
     */
    private function deduplicateCompressionPattern($matches, &$pattern)
    {
        $tmp_matches = $matches;
        $first_match = array_shift($tmp_matches);
        $differences = array();

        foreach ($tmp_matches as $some_match) {
            $differences += array_diff_assoc($first_match, $some_match);
        }

        $identical = array_diff_key($first_match, $differences);

        $prepared_matches = array();

        foreach ($matches as $i => $some_match) {
            $key = self::COMPRESSION_PATTERN_START
                . implode(
                    self::COMPRESSION_PATTERN_DELIMITER,
                    array_diff_assoc($some_match, $identical)
                );

            $prepared_matches[$key] = $i;
        }

        $pattern_parts = explode('(\d)', $pattern);

        foreach ($identical as $position => $value) {
            $pattern_parts[$position + 1] = $pattern_parts[$position] . $value . $pattern_parts[$position + 1];
            unset($pattern_parts[$position]);
        }

        $pattern = implode('(\d)', $pattern_parts);

        return $prepared_matches;
    }
}
