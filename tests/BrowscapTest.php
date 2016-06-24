<?php

namespace BrowscapPHPTest;

use BrowscapPHP\Browscap;

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
 * @group      browscap
 */
class BrowscapTest extends \PHPUnit_Framework_TestCase
{
    const STORAGE_DIR = 'storage';

    /**
     * @var \BrowscapPHP\Browscap
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $this->object = new Browscap();
    }

    /**
     *
     */
    public function testSetGetFormatter()
    {
        /** @var \BrowscapPHP\Formatter\PhpGetBrowser $formatter */
        $formatter = $this->getMockBuilder(\BrowscapPHP\Formatter\PhpGetBrowser::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertSame($this->object, $this->object->setFormatter($formatter));
        self::assertSame($formatter, $this->object->getFormatter());
    }

    /**
     *
     */
    public function testGetCache()
    {
        self::assertInstanceOf('\BrowscapPHP\Cache\BrowscapCache', $this->object->getCache());
    }

    /**
     *
     */
    public function testSetGetCache()
    {
        /** @var \BrowscapPHP\Cache\BrowscapCache $cache */
        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertSame($this->object, $this->object->setCache($cache));
        self::assertSame($cache, $this->object->getCache());
    }

    /**
     *
     */
    public function testSetGetCacheWithAdapter()
    {
        /** @var \WurflCache\Adapter\Memory $cache */
        $cache = $this->getMockBuilder(\WurflCache\Adapter\Memory::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertSame($this->object, $this->object->setCache($cache));
        self::assertInstanceOf('\BrowscapPHP\Cache\BrowscapCache', $this->object->getCache());
    }

    /**
     * @expectedException \BrowscapPHP\Exception
     * @expectedExceptionMessage the cache has to be an instance of \BrowscapPHP\Cache\BrowscapCacheInterface or an instanceof of \WurflCache\Adapter\AdapterInterface
     */
    public function testSetGetCacheWithWrongType()
    {
        $this->object->setCache('test');
    }

    /**
     *
     */
    public function testGetParser()
    {
        self::assertInstanceOf('\BrowscapPHP\Parser\Ini', $this->object->getParser());
    }

    /**
     *
     */
    public function testSetGetParser()
    {
        $parser = $this->getMockBuilder(\BrowscapPHP\Parser\Ini::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertSame($this->object, $this->object->setParser($parser));
        self::assertSame($parser, $this->object->getParser());
    }

    /**
     *
     */
    public function testGetLogger()
    {
        self::assertInstanceOf('\Psr\Log\NullLogger', $this->object->getLogger());
    }

    /**
     *
     */
    public function testSetGetLogger()
    {
        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertSame($this->object, $this->object->setLogger($logger));
        self::assertSame($logger, $this->object->getLogger());
    }

    /**
     * @expectedException \BrowscapPHP\Exception
     * @expectedExceptionMessage there is no active cache available, please run the update command
     */
    public function testGetBrowserWithoutCache()
    {
        $this->object->getBrowser();
    }

    /**
     *
     */
    public function testGetBrowserWithoutUa()
    {
        $browserObject          = new \StdClass();
        $browserObject->parent  = 'something';
        $browserObject->comment = 'an comment';

        $formatter = $this->getMockBuilder(\BrowscapPHP\Formatter\PhpGetBrowser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();
        $formatter
            ->expects(self::once())
            ->method('getData')
            ->will(self::returnValue($browserObject));

        $parser = $this->getMockBuilder(\BrowscapPHP\Parser\Ini::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBrowser'])
            ->getMock();
        $parser
            ->expects(self::once())
            ->method('getBrowser')
            ->will(self::returnValue($formatter));

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVersion'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getVersion')
            ->will(self::returnValue(1));

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);
        $this->object->setCache($cache);
        $result = $this->object->getBrowser();

        self::assertSame($browserObject, $result);
    }

    /**
     *
     */
    public function testGetBrowserWithUa()
    {
        $browserObject          = new \StdClass();
        $browserObject->parent  = 'something';
        $browserObject->comment = 'an comment';

        $formatter = $this->getMockBuilder(\BrowscapPHP\Formatter\PhpGetBrowser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();
        $formatter
            ->expects(self::once())
            ->method('getData')
            ->will(self::returnValue($browserObject));

        $parser = $this->getMockBuilder(\BrowscapPHP\Parser\Ini::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBrowser'])
            ->getMock();
        $parser
            ->expects(self::once())
            ->method('getBrowser')
            ->will(self::returnValue($formatter));

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVersion'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getVersion')
            ->will(self::returnValue(1));

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);
        $this->object->setCache($cache);
        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        self::assertSame($browserObject, $result);
    }

    /**
     *
     */
    public function testGetBrowserWithDefaultResult()
    {
        $formatter = $this->getMockBuilder(\BrowscapPHP\Formatter\PhpGetBrowser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();
        $formatter
            ->expects(self::once())
            ->method('getData')
            ->will(self::returnValue(null));

        $parser = $this->getMockBuilder(\BrowscapPHP\Parser\Ini::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBrowser'])
            ->getMock();
        $parser
            ->expects(self::once())
            ->method('getBrowser')
            ->will(self::returnValue(null));

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVersion'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getVersion')
            ->will(self::returnValue(1));

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);
        $this->object->setCache($cache);
        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        self::assertNull($result);
    }
}
