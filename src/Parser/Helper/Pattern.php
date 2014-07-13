<?php
namespace phpbrowscap\Parser\Helper;

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
class Pattern
{
    /**
     * Number of pattern to combine for a faster regular expression search.
     *
     * @important The number of patterns that can be processed in one step
     *            is limited by the internal regular expression limits.
     * @var int
     */
    private static $joinPatterns = 100;

    /**
     * Creates new pattern cache files
     */
    public static function createPatterns()
    {
        // get all relevant patterns from the INI file
        // - containing "*" or "?"
        // - not containing "*" or "?", but not having a comment
        preg_match_all('/(?<=\[)(?:[^\r\n]*[?*][^\r\n]*)(?=\])|(?<=\[)(?:[^\r\n*?]+)(?=\])(?![^\[]*Comment=)/m', self::getContent(), $matches);
        $matches  = $matches[0];
        $contents = array();

        if (count($matches)) {
            // build an array to structure the data. this requires some memory, but we need this step to be able to
            // sort the data in the way we need it (see below).
            $data = array();
            foreach ($matches as $match) {
                // get the first characters for a fast search
                $tmp_start  = self::getPatternStart($match);
                $tmp_length = self::getPatternLength($match);

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
            }

            // sorting of the data is important to check the patterns later in the correct order, because
            // we need to check the most specific (=longest) patterns first, and the least specific
            // (".*" for "Default Browser")  last.
            //
            // sort by pattern start to group them
            ksort($data);
            // and then by pattern length (longest first)
            foreach (array_keys($data) as $key) {
                krsort($data[$key]);
            }

            // write optimized file (grouped by the first character of the has, generated from the pattern
            // start) with multiple patterns joined by tabs. this is to speed up loading of the data (small
            // array with pattern strings instead of an large array with single patterns) and also enables
            // us to search for multiple patterns in one preg_match call for a fast first search
            // (3-10 faster), followed by a detailed search for each single pattern.
            foreach ($data as $tmp_start => $tmp_entries) {
                foreach ($tmp_entries as $tmp_length => $tmp_patterns) {
                    for ($i = 0, $j = ceil(count($tmp_patterns)/self::$joinPatterns); $i < $j; $i++) {
                        $tmp_joinpatterns = implode("\t", array_slice($tmp_patterns, ($i * self::$joinPatterns), self::$joinPatterns));
                        $tmp_subkey       = self::getPatternCacheSubkey($tmp_start);
                        if (!isset($contents[$tmp_subkey])) {
                            $contents[$tmp_subkey] = '';
                        }
                        $contents[$tmp_subkey] .= $tmp_start . " " . $tmp_length . " " . $tmp_joinpatterns . "\n";
                    }
                }
            }
        }

        return $contents;
    }

    /**
     * Gets the subkey for the pattern cache file, generated from the given string
     *
     * @param string $string
     * @return string
     */
    public static function getPatternCacheSubkey($string) {
        return $string[0] . $string[1];
    }

    /**
     * Gets a hash from the first charcters of a pattern/user agent, that can be used for a fast comparison,
     * by comparing only the hashes, without having to match the complete pattern against the user agent.
     *
     * @param string $pattern
     * @return string
     */
    public static function getPatternStart($pattern)
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
    protected static function getPatternLength($pattern)
    {
        return strlen(str_replace('*', '', $pattern));
    }
}