<?php

namespace phpbrowscapTest\Cache;

use phpbrowscap\Cache\BrowscapCache;

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
class BrowscapCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \phpbrowscap\Cache\BrowscapCache
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $adapter = $this->getMock('\WurflCache\Adapter\File', array(), array(), '', false);

        $this->object = new BrowscapCache($adapter);
    }

    /**
     *
     */
    public function testSetGetCacheAdapter()
    {
        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setCacheAdapter($adapter));
        self::assertSame($adapter, $this->object->getCacheAdapter());
    }

    /**
     *
     */
    public function testSetUpdateInterval()
    {
        self::assertSame($this->object, $this->object->setUpdateInterval(1));
    }

    /**
     *
     */
    public function testGetVersionNotCached()
    {
        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array('hasItem', 'getItem'), array(), '', false);
        $adapter
            ->expects(self::once())
            ->method('hasItem')
            ->will(self::returnValue(false))
        ;
        $adapter
            ->expects(self::never())
            ->method('getItem')
            ->will(self::returnValue(false))
        ;

        $this->object->setCacheAdapter($adapter);
        self::assertNull($this->object->getVersion());
    }

    /**
     *
     */
    public function testGetVersionCached()
    {
        $data = array(
            'content'      => serialize(42)
        );

        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array('hasItem', 'getItem'), array(), '', false);
        $adapter
            ->expects(self::once())
            ->method('hasItem')
            ->will(self::returnValue(true))
        ;
        $adapter
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValue($data))
        ;

        $this->object->setCacheAdapter($adapter);
        self::assertSame(42, $this->object->getVersion());
    }

    /**
     *
     */
    public function testGetItemNotCached()
    {
        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array('hasItem', 'getItem'), array(), '', false);
        $adapter
            ->expects(self::once())
            ->method('hasItem')
            ->will(self::returnValue(false))
        ;
        $adapter
            ->expects(self::never())
            ->method('getItem')
            ->will(self::returnValue(false))
        ;

        $this->object->setCacheAdapter($adapter);
        $success = null;
        self::assertNull($this->object->getItem('test', false, $success));
        self::assertFalse($success);
    }

    /**
     *
     */
    public function testGetItemCached()
    {
        $data = array(
            'content'      => serialize(42)
        );

        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array('hasItem', 'getItem'), array(), '', false);
        $adapter
            ->expects(self::once())
            ->method('hasItem')
            ->will(self::returnValue(true))
        ;
        $adapter
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValue($data))
        ;

        $this->object->setCacheAdapter($adapter);
        $success = null;
        self::assertSame(42, $this->object->getItem('test', false, $success));
        self::assertTrue($success);
    }

    /**
     *
     */
    public function testGetItemCachedWithVersion()
    {
        $map = array(
            array(
                'browscap.version',
                null,
                array(
                    'content'      => serialize(42)
                )
            ),
            array(
                'test.42',
                null,
                array(
                    'content'      => serialize('this is a test')
                )
            )
        );

        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array('hasItem', 'getItem'), array(), '', false);
        $adapter
            ->expects(self::exactly(2))
            ->method('hasItem')
            ->will(self::returnValue(true))
        ;
        $adapter
            ->expects(self::exactly(2))
            ->method('getItem')
            ->will(self::returnValueMap($map))
        ;

        $this->object->setCacheAdapter($adapter);
        $success = null;
        self::assertSame('this is a test', $this->object->getItem('test', true, $success));
        self::assertTrue($success);
    }

    /**
     *
     */
    public function testHasItemNotCached()
    {
        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array('hasItem'), array(), '', false);
        $adapter
            ->expects(self::once())
            ->method('hasItem')
            ->will(self::returnValue(false))
        ;

        $this->object->setCacheAdapter($adapter);
        self::assertFalse($this->object->hasItem('test', false));
    }

    /**
     *
     */
    public function testHasItemCachedWithVersion()
    {
        $data = array(
            'content'      => serialize(42)
        );

        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array('hasItem', 'getItem'), array(), '', false);
        $adapter
            ->expects(self::exactly(2))
            ->method('hasItem')
            ->will(self::returnValue(true))
        ;
        $adapter
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValue($data))
        ;

        $this->object->setCacheAdapter($adapter);
        self::assertTrue($this->object->hasItem('test', true));
    }

    /**
     *
     */
    public function testSetItemWithVersion()
    {
        $data = array(
            'content'      => serialize(42)
        );

        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array('hasItem', 'getItem', 'setItem'), array(), '', false);
        $adapter
            ->expects(self::once())
            ->method('hasItem')
            ->will(self::returnValue(true))
        ;
        $adapter
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValue($data))
        ;
        $adapter
            ->expects(self::once())
            ->method('setItem')
            ->will(self::returnValue(true))
        ;

        $this->object->setCacheAdapter($adapter);
        self::assertTrue($this->object->setItem('test', true, true));
    }

    /**
     *
     */
    public function testRemoveItemWithVersion()
    {
        $data = array(
            'content'      => serialize(42)
        );

        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array('hasItem', 'getItem', 'removeItem'), array(), '', false);
        $adapter
            ->expects(self::once())
            ->method('hasItem')
            ->will(self::returnValue(true))
        ;
        $adapter
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValue($data))
        ;
        $adapter
            ->expects(self::once())
            ->method('removeItem')
            ->will(self::returnValue(true))
        ;

        $this->object->setCacheAdapter($adapter);
        self::assertTrue($this->object->removeItem('test', true));
    }

    /**
     *
     */
    public function testFlush()
    {
        $data = array(
            'content'      => serialize(42)
        );

        $adapter = $this->getMock('\WurflCache\Adapter\Memcache', array('hasItem', 'getItem', 'flush'), array(), '', false);
        $adapter
            ->expects(self::never())
            ->method('hasItem')
            ->will(self::returnValue(true))
        ;
        $adapter
            ->expects(self::never())
            ->method('getItem')
            ->will(self::returnValue($data))
        ;
        $adapter
            ->expects(self::once())
            ->method('flush')
            ->will(self::returnValue(true))
        ;

        $this->object->setCacheAdapter($adapter);
        self::assertTrue($this->object->flush());
    }
}
