<?php

namespace BrowscapPHPTest\Util\LogFile;

use BrowscapPHP\Util\Logfile\ApacheCommonLogFormatReader;

/**
 * Browscap.ini parsing class with caching and update capabilities
 *
 * PHP version 5
 *
 * Copyright (c) 2006-2012 Jonathan Stoppani
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
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class ApacheCommonLogFormatReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BrowscapPHP\Util\Logfile\ApacheCommonLogFormatReader
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $this->object = new ApacheCommonLogFormatReader();
    }

    /**
     *
     */
    public function testTestFails()
    {
        self::assertFalse($this->object->test('test'));
    }

    /**
     * @expectedException \BrowscapPHP\Exception\ReaderException
     * @expectedExceptionMessage test
     */
    public function testReadFails()
    {
        $this->object->read('test');
    }

    /**
     * data provider for the function testTestOk
     */
    public function regexproviderOk()
    {
        return [
            ['87.139.99.29 - - 6 0 - - [07/Aug/2014:18:36:10 +0200] - "-" 408 - "-" "-" - www.geld.de'],
        ];
    }

    /**
     * @dataProvider regexproviderOk
     *
     * @param string $ua
     */
    public function testTestOk($ua)
    {
        self::assertTrue($this->object->test($ua));
    }
}
