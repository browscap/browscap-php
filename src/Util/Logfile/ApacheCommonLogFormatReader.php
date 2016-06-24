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

namespace BrowscapPHP\Util\Logfile;

/**
 * reader to analyze the common log file of apache
 *
 * @category   Browscap-PHP
 * @author     Dave Olsen, http://dmolsen.com
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class ApacheCommonLogFormatReader extends AbstractReader
{
    /**
     * @return string
     */
    protected function getRegex()
    {
        return '/^'
            . '(\S+)'                            # remote host (IP)
            . '\s+'
            . '(\S+)'                            # remote logname
            . '\s+'
            . '(\S+)'                            # remote user
            . '.*'
            . '\[([^]]+)\]'                      # date/time
            . '[^"]+'
            . '\"(.*)\"'                         # Verb(GET|POST|HEAD) Path HTTP Version
            . '\s+'
            . '(.*)'                             # Status
            . '\s+'
            . '(.*)'                             # Length (include Header)
            . '[^"]+'
            . '\"(.*)\"'                         # Referrer
            . '[^"]+'
            . '\"(?P<userAgentString>.+?)\".*'   # User Agent
            . '$/x';
    }
}
