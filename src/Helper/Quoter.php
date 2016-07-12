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

/**
 * class to help quoting strings for using a regex
 *
 * @category   Browscap-PHP
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class Quoter
{
    /**
     * Converts browscap match patterns into preg match patterns.
     *
     * @param string $user_agent
     * @param string $delimiter
     *
     * @return string
     */
    public function pregQuote($user_agent, $delimiter = '/')
    {
        $pattern = preg_quote($user_agent, $delimiter);

        // the \\x replacement is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
        return str_replace(['\*', '\?', '\\x'], ['.*', '.', '\\\\x'], $pattern);
    }

    /**
     * Reverts the quoting of a pattern.
     *
     * @param  string $pattern
     * @return string
     */
    public function pregUnQuote($pattern)
    {
        // Fast check, because most parent pattern like 'DefaultProperties' don't need a replacement
        if (preg_match('/[^a-z\s]/i', $pattern)) {
            // Undo the \\x replacement, that is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
            // @source https://github.com/browscap/browscap-php
            $pattern = preg_replace(
                ['/(?<!\\\\)\\.\\*/', '/(?<!\\\\)\\./', '/(?<!\\\\)\\\\x/'],
                ['\\*', '\\?', '\\x'],
                $pattern
            );

            // Undo preg_quote
            $pattern = str_replace(
                [
                    '\\\\', '\\+', '\\*', '\\?', '\\[', '\\^', '\\]', '\\$', '\\(', '\\)', '\\{', '\\}', '\\=',
                    '\\!', '\\<', '\\>', '\\|', '\\:', '\\-', '\\.', '\\/',
                ],
                [
                    '\\', '+', '*', '?', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|', ':',
                    '-', '.', '/',
                ],
                $pattern
            );
        }

        return $pattern;
    }
}
