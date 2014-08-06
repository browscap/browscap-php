<?php

namespace phpbrowscapTest\Helper;

use phpbrowscap\Helper\IniLoader;

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
 * @package    Browscap
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/GaretJax/phpbrowscap/
 */
class IniLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \phpbrowscap\Helper\IniLoader
     */
    private $object = null;

    public function setUp()
    {
        $this->object = new IniLoader();
    }

    /**
     *
     */
    public function testSetGetLoader()
    {
        $loader = $this->getMock('\FileLoader\Loader', array(), array(), '', false);
		
		self::assertSame($this->object, $this->object->setLoader($loader));
		self::assertSame($loader, $this->object->getLoader());
		
		self::assertSame($this->object, $this->object->setLocalFile('test'));
		self::assertSame($loader, $this->object->getLoader());
    }

    /**
     *
     */
    public function testSetGetLogger()
    {
        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);
        
        self::assertSame($this->object, $this->object->setLogger($logger));
        self::assertSame($logger, $this->object->getLogger());
    }

    /**
     * @expectedException \phpbrowscap\Helper\Exception
	 * @expectedExceptionMessage the filename can not be empty
     */
    public function testSetMissingRemoteFilename()
    {
        self::assertSame($this->object, $this->object->setRemoteFilename());
    }

    /**
     *
     */
    public function testSetRemoteFilename()
    {
        self::assertSame($this->object, $this->object->setRemoteFilename('testFile'));
    }

    /**
     * @expectedException \phpbrowscap\Helper\Exception
	 * @expectedExceptionMessage the filename can not be empty
     */
    public function testSetMissingLocalFile()
    {
        self::assertSame($this->object, $this->object->setLocalFile());
    }

    /**
     *
     */
    public function testSetLocalFile()
    {
        self::assertSame($this->object, $this->object->setLocalFile('testFile'));
    }

    /**
     *
     */
    public function testGetRemoteIniUrl()
    {
		$this->object->setRemoteFilename(IniLoader::PHP_INI_LITE);
        self::assertSame('http://browscap.org/stream?q=Lite_PHP_BrowscapINI', $this->object->getRemoteIniUrl());
		
		$this->object->setRemoteFilename(IniLoader::PHP_INI_FULL);
        self::assertSame('http://browscap.org/stream?q=Full_PHP_BrowscapINI', $this->object->getRemoteIniUrl());
    }

    /**
     *
     */
    public function testGetRemoteVerUrl()
    {
        self::assertSame('http://browscap.org/version', $this->object->getRemoteVerUrl());
    }

    /**
     *
     */
    public function testGetTimeout()
    {
        self::assertSame(5, $this->object->getTimeout());
    }

    /**
     *
     */
    public function testSetOptions()
    {
		$options = array();
		
        self::assertSame($this->object, $this->object->setOptions($options));
    }

    /**
     *
     */
    public function testLoad()
    {
		$loader = $this->getMock('\FileLoader\Loader', array('load', 'setRemoteDataUrl', 'setRemoteVerUrl', 'setTimeout', 'setLogger'), array(), '', false);
		$loader
            ->expects(self::once())
            ->method('load')
            ->will(self::returnValue(true))
        ;
		$loader
            ->expects(self::once())
            ->method('setRemoteDataUrl')
            ->will(self::returnSelf())
        ;
		$loader
            ->expects(self::once())
            ->method('setRemoteVerUrl')
            ->will(self::returnSelf())
        ;
		$loader
            ->expects(self::once())
            ->method('setTimeout')
            ->will(self::returnSelf())
        ;
		$loader
            ->expects(self::once())
            ->method('setLogger')
            ->will(self::returnSelf())
        ;
		
		$this->object->setLoader($loader);
		
		$logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);
        
        $this->object->setLogger($logger);
		
        self::assertTrue($this->object->load());
    }
}
