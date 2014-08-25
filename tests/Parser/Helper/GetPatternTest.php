<?php

namespace phpbrowscapTest\Parser\Helper;

use phpbrowscap\Parser\Helper\GetPattern;
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
class GetPatternTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Browscap\Parser\Helper\GetPattern
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        if (version_compare(PHP_VERSION, '5.5.0') < 0) {
            $this->markTestSkipped('not supported by this PHP version');
        }

        $this->object = new GetPattern();
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
    public function testSetGetLogger()
    {
        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setLogger($logger));
        self::assertSame($logger, $this->object->getLogger());
    }

    /**
     *
     */
    public function testGetPatterns()
    {
        $map = array(
            array(
                'browscap.version',
                null,
                array(
                    'cacheVersion' => BrowscapCache::CACHE_FILE_VERSION,
                    'content'      => serialize(42)
                )
            ),
            array(
                'test.42',
                null,
                array(
                    'cacheVersion' => BrowscapCache::CACHE_FILE_VERSION,
                    'content'      => serialize('this is a test')
                )
            )
        );

        $cache = $this->getMock('\phpbrowscap\Cache\BrowscapCache', array('getItem'), array(), '', false);
        $cache
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValueMap($map))
        ;

        $this->object->setCache($cache);

        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);
        $this->object->setLogger($logger);

        $result = $this->object->getPatterns('Mozilla/5.0 (compatible; Ask Jeeves/Teoma*)');

        self::assertInstanceOf('Generator', $result);
    }
}
