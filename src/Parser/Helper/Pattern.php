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

/**
 * includes general functions for the work with patterns
 *
 * @category   Browscap-PHP
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class Pattern
{
    /**
     * Gets a hash or an array of hashes from the first characters of a pattern/user agent, that can
     * be used for a fast comparison, by comparing only the hashes, without having to match the
     * complete pattern against the user agent.
     *
     * With the variants options, all variants from the maximum number of pattern characters to one
     * character will be returned. This is required in some cases, the a placeholder is used very
     * early in the pattern.
     *
     * Example:
     *
     * Pattern: "Mozilla/* (Nintendo 3DS; *) Version/*"
     * User agent: "Mozilla/5.0 (Nintendo 3DS; U; ; en) Version/1.7567.US"
     *
     * In this case the has for the pattern is created for "Mozilla/" while the pattern
     * for the hash for user agent is created for "Mozilla/5.0". The variants option
     * results in an array with hashes for "Mozilla/5.0", "Mozilla/5.", "Mozilla/5",
     * "Mozilla/" ... "M", so that the pattern hash is included.
     *
     * @param  string       $pattern
     * @param  bool         $variants
     * @return string|array
     */
    public static function getHashForPattern($pattern, $variants = false)
    {
        $regex   = '/^([^\.\*\?\s\r\n\\\\]+).*$/';
        $pattern = substr($pattern, 0, 32);
        $matches = [];

        if (!preg_match($regex, $pattern, $matches) || !isset($matches[1])) {
            return ($variants ? [md5('')] : md5(''));
        }

        $string = $matches[1];

        if (true === $variants) {
            $patternStarts = [];

            for ($i = strlen($string); $i >= 1; --$i) {
                $string          = substr($string, 0, $i);
                $patternStarts[] = md5($string);
            }

            // Add empty pattern start to include patterns that start with "*",
            // e.g. "*FAST Enterprise Crawler*"
            $patternStarts[] = md5('');

            return $patternStarts;
        }

        return md5($string);
    }

    /**
     * returns a hash for one pattern
     *
     * @param $pattern
     *
     * @return string
     */
    public static function getHashForParts($pattern)
    {
        return md5($pattern);
    }

    /**
     * Gets the minimum length of the patern (used in the getPatterns() method to
     * check against the user agent length)
     *
     * @param  string $pattern
     * @return int
     */
    public static function getPatternLength($pattern)
    {
        return strlen(str_replace('*', '', $pattern));
    }
}
