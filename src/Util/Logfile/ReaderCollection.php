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

use BrowscapPHP\Exception\ReaderException;

/**
 * reader collection class
 *
 * @category   Browscap-PHP
 * @author     Dave Olsen, http://dmolsen.com
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class ReaderCollection implements ReaderInterface
{
    /**
     * @var \BrowscapPHP\Util\Logfile\AbstractReader[]
     */
    private $readers = [];

    /**
     * adds a new reader to this collection
     *
     * @param \BrowscapPHP\Util\Logfile\ReaderInterface $reader
     *
     * @return \BrowscapPHP\Util\Logfile\ReaderCollection
     */
    public function addReader(ReaderInterface $reader)
    {
        $this->readers[] = $reader;

        return $this;
    }

    /**
     * @param string $line
     *
     * @return bool
     */
    public function test($line)
    {
        foreach ($this->readers as $reader) {
            if ($reader->test($line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $line
     *
     * @throws \BrowscapPHP\Exception\ReaderException
     * @return string
     */
    public function read($line)
    {
        foreach ($this->readers as $reader) {
            if ($reader->test($line)) {
                return $reader->read($line);
            }
        }

        throw ReaderException::userAgentParserError($line);
    }
}
