<?php

namespace phpbrowscapTest;

use phpbrowscap\Detector;

/**
 * Detector.ini parsing class with caching and update capabilities
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
 * @package    Detector
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/GaretJax/phpbrowscap/
 */
class DetectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Detector
     */
    private $object = null;

    /**
     * @var string
     */
    private static $cacheDir = null;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        $cacheDir = 'tests/browscap_testing';

        if (!is_dir($cacheDir)) {
            if (false === @mkdir($cacheDir, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create the "%s" directory', $cacheDir));
            }
        }

        self::$cacheDir = realpath($cacheDir);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testConstructorFails()
    {
        new Detector();
    }

    /**
     * @expectedException \phpbrowscap\Exception
     * @expectedExceptionMessage You have to provide a path to read/store the browscap cache file
     */
    public function testConstructorFails2()
    {
        new Detector(null);
    }

    /**
     *
     */
    public function testConstructorFails3()
    {
        $path = '/abc/test';

        $this->setExpectedException(
            '\phpbrowscap\Exception',
            'The cache path "' . $path . '" is invalid. Are you sure that it exists and that you have permission to access it?'
        );

        new Detector($path);
    }


    /**
     * tests the auto detection of the proxy settings from the envirionment
     */
    public function testProxyAutoDetection()
    {
        self::markTestSkipped('not finished yet');
    }

    /**
     * tests the setting of a cache instance
     */
    public function testSetGetCache()
    {
        $cache = $this->getMock('\WurflCache\Adapter\NullStorage', array(), array(), '', false);

        $object = new Detector(self::$cacheDir);

        self::assertSame($object, $object->setCache($cache));
        self::assertSame($cache, $object->getCache());
    }

    /**
     * tests the setting of a cache instance
     */
    public function testSetLogger()
    {
        $logger = $this->getMock('\Psr\Log\NullLogger', array(), array(), '', false);

        $object = new Detector(self::$cacheDir);

        self::assertSame($object, $object->setLogger($logger));
    }

    /**
     * tests the setting of a cache instance
     */
    public function testSetCachePrefix()
    {
        $object = new Detector(self::$cacheDir);

        self::assertSame($object, $object->setCachePrefix('abc'));
    }

    /**
     * tests the setting of a cache instance
     */
    public function testSetLocaleFileException()
    {
        $this->setExpectedException(
            '\phpbrowscap\Exception',
            'the filename can not be empty'
        );

        $object = new Detector(self::$cacheDir);

        $object->setLocaleFile(null);
    }

    /**
     * tests the setting of a cache instance
     */
    public function testSetLocaleFile()
    {
        $object = new Detector(self::$cacheDir);

        self::assertSame($object, $object->setLocaleFile('test.ini'));
    }
}
