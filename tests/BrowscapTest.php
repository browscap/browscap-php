<?php

namespace phpbrowscapTest;

use phpbrowscap\Browscap;

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
class BrowscapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \phpbrowscap\Browscap
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
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
        $formatter = $this->getMock('\phpbrowscap\Formatter\PhpGetBrowser', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setFormatter($formatter));
        self::assertSame($formatter, $this->object->getFormatter());
    }

    /**
     *
     */
    public function testGetCache()
    {
        self::assertInstanceOf('\phpbrowscap\Cache\BrowscapCache', $this->object->getCache());
    }

    /**
     *
     */
    public function testSetGetCache()
    {
        $cache = $this->getMock('\phpbrowscap\Cache\BrowscapCache', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setCache($cache));
        self::assertSame($cache, $this->object->getCache());
    }

    /**
     *
     */
    public function testSetGetCacheWithAdapter()
    {
        $cache = $this->getMock('\WurflCache\Adapter\Memory', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setCache($cache));
        self::assertInstanceOf('\phpbrowscap\Cache\BrowscapCache', $this->object->getCache());
    }

    /**
     * @expectedException \phpbrowscap\Exception
     * @expectedExceptionMessage the cache has to be an instance of \phpbrowscap\Cache\BrowscapCache or an instanceof of \WurflCache\Adapter\AdapterInterface
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
        self::assertInstanceOf('\phpbrowscap\Parser\Ini', $this->object->getParser());
    }

    /**
     *
     */
    public function testSetGetParser()
    {
        $parser = $this->getMock(
            '\phpbrowscap\Parser\Ini',
            array('setHelper', 'setFormatter', 'setCache', 'setLogger'),
            array(),
            '',
            false
        );
        $parser
            ->expects(self::once())
            ->method('setHelper')
            ->will(self::returnSelf())
        ;
        $parser
            ->expects(self::once())
            ->method('setFormatter')
            ->will(self::returnSelf())
        ;
        $parser
            ->expects(self::once())
            ->method('setCache')
            ->will(self::returnSelf())
        ;
        $parser
            ->expects(self::once())
            ->method('setLogger')
            ->will(self::returnSelf())
        ;

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
        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setLogger($logger));
        self::assertSame($logger, $this->object->getLogger());
    }

    /**
     *
     */
    public function testSetOptions()
    {
        $options = array();

        self::assertSame($this->object, $this->object->setOptions($options));
    }
}
