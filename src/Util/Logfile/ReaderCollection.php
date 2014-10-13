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
 * @package    Util\Logfile
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Util\Logfile;

use phpbrowscap\Exception\ReaderException;

/**
 * reader collection class
 *
 * @category   Browscap-PHP
 * @package    Command
 * @author     Dave Olsen, http://dmolsen.com
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class ReaderCollection implements ReaderInterface
{
    /**
     * @var \phpbrowscap\Util\Logfile\AbstractReader[]
     */
    private $readers = array();

    /**
     * adds a new reader to this collection
     *
     * @param \phpbrowscap\Util\Logfile\AbstractReader $reader
     *
     * @return \phpbrowscap\Util\Logfile\ReaderCollection
     */
    public function addReader(AbstractReader $reader)
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
     * @return string
     * @throws \phpbrowscap\Exception\ReaderException
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
