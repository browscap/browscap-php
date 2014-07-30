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
 * @package    Helper
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Helper;

/**
 * class to help quoting strings for using a regex
 *
 * @category   Browscap-PHP
 * @package    Helper
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
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
        return str_replace(array('\*', '\?', '\\x'), array('.*', '.', '\\\\x'), $pattern);
    }

    /**
     * Converts preg match patterns back to browscap match patterns.
     *
     * @param string        $pattern
     * @param array|boolean $matches
     * @param string        $delimiter
     *
     * @return string
     */
    public function pregUnQuote($pattern, $matches, $delimiter = '/')
    {
        // list of escaped characters: http://www.php.net/manual/en/function.preg-quote.php
        // to properly unescape '?' which was changed to '.', I replace '\.' (real dot) with '\?', then change '.' to '?' and then '\?' to '.'.
        $search  = array(
            '\\' . $delimiter, '\\.', '\\\\', '\\+', '\\[', '\\^', '\\]', '\\$', '\\(', '\\)', '\\{', '\\}',
            '\\=', '\\!', '\\<', '\\>', '\\|', '\\:', '\\-', '.*', '.', '\\?'
        );
        $replace = array(
            $delimiter, '\\?', '\\', '+', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|',
            ':', '-', '*', '?', '.'
        );

        $result = substr(str_replace($search, $replace, $pattern), 2, -2);

        if (is_array($matches)) {
            foreach ($matches as $one_match) {
                $num_pos = strpos($result, '(\d)');
                $result  = substr_replace($result, $one_match, $num_pos, 4);
            }
        }

        return $result;
    }
}
