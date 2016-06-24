<?php

namespace BrowscapPHPTest\Util\LogFile;

use BrowscapPHP\Util\Logfile\ReaderCollection;

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
class ReaderCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BrowscapPHP\Util\Logfile\ReaderCollection
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $this->object = new ReaderCollection();
    }

    /**
     *
     */
    public function testaddReader()
    {
        /** @var \BrowscapPHP\Util\Logfile\ApacheCommonLogFormatReader $reader */
        $reader = $this->getMockBuilder(\BrowscapPHP\Util\Logfile\ApacheCommonLogFormatReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertSame($this->object, $this->object->addReader($reader));
    }

    /**
     *
     */
    public function testTestSuccessFull()
    {
        /** @var \BrowscapPHP\Util\Logfile\ApacheCommonLogFormatReader $reader */
        $reader = $this->getMockBuilder(\BrowscapPHP\Util\Logfile\ApacheCommonLogFormatReader::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();
        $reader
            ->expects(self::once())
            ->method('test')
            ->will(self::returnValue(true));

        $this->object->addReader($reader);

        self::assertTrue($this->object->test('Test'));
    }

    /**
     *
     */
    public function testTestNotSuccessFull()
    {
        /** @var \BrowscapPHP\Util\Logfile\ApacheCommonLogFormatReader $reader */
        $reader = $this->getMockBuilder(\BrowscapPHP\Util\Logfile\ApacheCommonLogFormatReader::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();
        $reader
            ->expects(self::once())
            ->method('test')
            ->will(self::returnValue(false));

        $this->object->addReader($reader);

        self::assertFalse($this->object->test('Test'));
    }

    /**
     *
     */
    public function testReadSuccessFull()
    {
        $reader = $this->getMockBuilder(\BrowscapPHP\Util\Logfile\ApacheCommonLogFormatReader::class)
            ->disableOriginalConstructor()
            ->setMethods(['test', 'read'])
            ->getMock();
        $reader
            ->expects(self::once())
            ->method('test')
            ->will(self::returnValue(true));
        $reader
            ->expects(self::once())
            ->method('read')
            ->will(self::returnValue('TestUA'));

        $this->object->addReader($reader);

        self::assertSame('TestUA', $this->object->read('Test'));
    }

    /**
     * @expectedException \BrowscapPHP\Exception\ReaderException
     * @expectedExceptionMessage Cannot extract user agent string from line "Test"
     */
    public function testReadNotSuccessFull()
    {
        $reader = $this->getMockBuilder(\BrowscapPHP\Util\Logfile\ApacheCommonLogFormatReader::class)
            ->disableOriginalConstructor()
            ->setMethods(['test', 'read'])
            ->getMock();
        $reader
            ->expects(self::once())
            ->method('test')
            ->will(self::returnValue(false));
        $reader
            ->expects(self::never())
            ->method('read')
            ->will(self::returnValue('TestUA'));

        $this->object->addReader($reader);
        $this->object->read('Test');
    }
}
