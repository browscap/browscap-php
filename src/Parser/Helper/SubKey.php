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
class SubKey
{
    /**
     * Gets the subkey for the pattern cache file, generated from the given string
     *
     * @param  string $string
     * @return string
     */
    public static function getPatternCacheSubkey($string)
    {
        return $string[0] . $string[1];
    }

    /**
     * Gets all subkeys for the pattern cache files
     *
     * @return array
     */
    public static function getAllPatternCacheSubkeys()
    {
        $subkeys = [];
        $chars   = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];

        foreach ($chars as $charOne) {
            foreach ($chars as $charTwo) {
                $subkeys[$charOne . $charTwo] = '';
            }
        }

        return $subkeys;
    }

    /**
     * Gets the sub key for the ini parts cache file, generated from the given string
     *
     * @param  string $string
     * @return string
     */
    public static function getIniPartCacheSubKey($string)
    {
        return $string[0] . $string[1] . $string[2];
    }

    /**
     * Gets all sub keys for the inipart cache files
     *
     * @return array
     */
    public static function getAllIniPartCacheSubKeys()
    {
        $subKeys = [];
        $chars   = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];

        foreach ($chars as $charOne) {
            foreach ($chars as $charTwo) {
                foreach ($chars as $charThree) {
                    $subKeys[] = $charOne . $charTwo . $charThree;
                }
            }
        }

        return $subKeys;
    }
}
