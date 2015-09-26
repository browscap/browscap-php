<?php

namespace phpbrowscapTest;

use phpbrowscap\Browscap;
use ReflectionClass;

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
class BrowscapTest
    extends TestCase
{
    /**
     * @expectedException \phpbrowscap\Exception
     * @expectedExceptionMessage You have to provide a path to read/store the browscap cache file
     */
    public function testConstructorFailsWithoutPath()
    {
        new Browscap();
    }

    /**
     * @expectedException \phpbrowscap\Exception
     * @expectedExceptionMessage You have to provide a path to read/store the browscap cache file
     */
    public function testConstructorFailsWithNullPath()
    {
        new Browscap(null);
    }

    /**
     *
     */
    public function testConstructorFailsWithInvalidPath()
    {
        $path = '/abc/test';

        $this->setExpectedException(
            '\\phpbrowscap\\Exception',
            'The cache path ' . $path
            . ' is invalid. Are you sure that it exists and that you have permission to access it?'
        );

        new Browscap($path);
    }

    public function testProxyAutoDetection()
    {
        $browscap = $this->createBrowscap();

        putenv('http_proxy=http://proxy.example.com:3128');
        putenv('https_proxy=http://proxy.example.com:3128');
        putenv('ftp_proxy=http://proxy.example.com:3128');

        $browscap->autodetectProxySettings();
        $options = $browscap->getStreamContextOptions();

        self::assertEquals($options['http']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertTrue($options['http']['request_fulluri']);

        self::assertEquals($options['https']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertTrue($options['https']['request_fulluri']);

        self::assertEquals($options['ftp']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertTrue($options['ftp']['request_fulluri']);
    }

    public function testAddProxySettings()
    {
        $browscap = $this->createBrowscap();

        $browscap->addProxySettings('proxy.example.com', 3128, 'http');
        $options = $browscap->getStreamContextOptions();

        self::assertEquals($options['http']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertTrue($options['http']['request_fulluri']);
    }

    public function testAddProxySettingsWithUsername()
    {
        $browscap = $this->createBrowscap();

        $browscap->addProxySettings('proxy.example.com', 3128, 'http', 'test', 'test');
        $options = $browscap->getStreamContextOptions();

        self::assertEquals($options['http']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertEquals($options['http']['header'], 'Proxy-Authorization: Basic dGVzdDp0ZXN0');
        self::assertTrue($options['http']['request_fulluri']);
    }

    public function testClearProxySettings()
    {
        $browscap = $this->createBrowscap();

        $browscap->addProxySettings('proxy.example.com', 3128, 'http');
        $options = $browscap->getStreamContextOptions();

        self::assertEquals($options['http']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertTrue($options['http']['request_fulluri']);

        $clearedWrappers = $browscap->clearProxySettings();
        $options         = $browscap->getStreamContextOptions();

        $defaultStreamContextOptions = array(
            'http' => array(
                'timeout' => $browscap->timeout,
            )
        );

        $this->assertEquals($defaultStreamContextOptions, $options);
        self::assertEquals($clearedWrappers, array('http'));
    }

    public function testGetStreamContext()
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_getStreamContext');
        $method->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        $browscap->addProxySettings('proxy.example.com', 3128, 'http');

        $resource = $method->invoke($browscap);

        self::assertTrue(is_resource($resource));
    }

    /**
     * @expectedException \phpbrowscap\Exception
     * @expectedExceptionMessage Local file is not readable
     */
    public function testGetLocalMTimeFails()
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_getLocalMTime');
        $method->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        $method->invoke($browscap);
    }

    /**
     *
     */
    public function testGetLocalMTime()
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_getLocalMTime');
        $method->setAccessible(true);

        $browscap            = new Browscap($cacheDir);
        $browscap->localFile = __FILE__;

        $mtime    = $method->invoke($browscap);
        $expected = filemtime(__FILE__);

        self::assertSame($expected, $mtime);
    }

    /**
     * @expectedException \phpbrowscap\Exception
     * @expectedExceptionMessage Bad datetime format from http://browscap.org/version
     */
    public function testGetRemoteMTimeFails()
    {
        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_getRemoteMTime');
        $method->setAccessible(true);

        $browscap = $this->getMock('\\phpbrowscap\\Browscap', array('_getRemoteData'), array(), '', false);
        $browscap->expects(self::any())
                 ->method('_getRemoteData')
                 ->will(self::returnValue(null))
        ;

        $method->invoke($browscap);
    }

    /**
     *
     */
    public function testGetRemoteMTime()
    {
        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_getRemoteMTime');
        $method->setAccessible(true);

        $expected = 'Mon, 29 Jul 2013 22:22:31 -0000';

        $browscap = $this->getMock('\\phpbrowscap\\Browscap', array('_getRemoteData'), array(), '', false);
        $browscap->expects(self::any())
                 ->method('_getRemoteData')
                 ->will(self::returnValue($expected))
        ;

        $mtime = $method->invoke($browscap);

        self::assertSame(strtotime($expected), $mtime);
    }

    /**
     * @group testCache
     */
    public function testArray2string()
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_array2string');
        $method->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        $xpected = "array(\n'a' => 1,\n'b' => 'abc',\n1 => 'cde',\n'def',\n'a:3:{i:0;s:3:\"abc\";i:1;i:1;i:2;i:2;}',\n\n)";

        self::assertSame(
            $xpected,
            $method->invoke(
                $browscap,
                array('a' => 1, 'b' => 'abc', '1.0' => 'cde', 1 => 'def', 2 => array('abc', 1, 2))
            )
        );
    }

    /**
     *
     */
    public function testGetUpdateMethodReturnsFopen()
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_getUpdateMethod');
        $method->setAccessible(true);

        $browscap               = new Browscap($cacheDir);
        $browscap->updateMethod = null;

        $expected = Browscap::UPDATE_FOPEN;

        self::assertSame($expected, $method->invoke($browscap));
    }

    /**
     *
     */
    public function testGetUpdateMethodReturnsLocal()
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_getUpdateMethod');
        $method->setAccessible(true);

        $browscap               = new Browscap($cacheDir);
        $browscap->updateMethod = null;
        $browscap->localFile    = __FILE__;

        $expected = Browscap::UPDATE_LOCAL;

        self::assertSame($expected, $method->invoke($browscap));
    }

    /**
     *
     */
    public function testGetUserAgent()
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_getUserAgent');
        $method->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        $expected = 'http://browscap.org/ - PHP Browscap/';

        self::assertContains($expected, $method->invoke($browscap));
    }

    /**
     *
     */
    public function testPregQuote()
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_pregQuote');
        $method->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        $expected = 'Mozilla/.\.0 \(compatible; Ask Jeeves/Teoma.*\)';

        self::assertSame($expected, $method->invoke($browscap, 'Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)'));
    }

    /**
     *
     */
    public function testPregUnQuote()
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_pregUnQuote');
        $method->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        $expected = 'Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)';

        self::assertSame(
            $expected,
            $method->invoke($browscap, '@^Mozilla/.\.0 \(compatible; Ask Jeeves/Teoma.*\)$@', array())
        )
        ;
    }

    /**
     * @dataProvider dataCompareBcStrings
     */
    public function testCompareBcStrings($a, $b, $expected)
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('compareBcStrings');
        $method->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        self::assertSame($expected, $method->invoke($browscap, $a, $b));
    }

    public function dataCompareBcStrings()
    {
        return array(
            array(
                'Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)',
                'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
                1
            ),
            array(
                'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
                'Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)',
                -1
            ),
            array(
                'Mozilla/5.0 (Danger hiptop 3.*; U; rv:1.7.*) Gecko/*',
                'Mozilla/5.0 (Danger hiptop 3.0; U; rv:1.7.*) Gecko/*',
                1
            ),
            array(
                'Mozilla/5.0 (Danger hiptop 3.0; U; rv:1.7.*) Gecko/*',
                'Mozilla/5.0 (Danger hiptop 3.*; U; rv:1.7.*) Gecko/*',
                -1
            ),
            array(
                'Mozilla/5.0 (Danger hiptop 3.0; U; rv:1.7.*) Gecko/*',
                'Mozilla/5.0 (Danger hiptop 3.0; U; rv:1.7.*) Gecko/*',
                0
            )
        );
    }

    /**
     * @dataProvider dataSanitizeContent
     */
    public function testSanitizeContent($content, $expected)
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('sanitizeContent');
        $method->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        self::assertSame($expected, $method->invoke($browscap, $content));
    }

    public function dataSanitizeContent()
    {
        return array(
            array(
                '[GJK_Browscap_Version]
Version=6004
Released=Wed, 10 Jun 2015 07:48:33 +0000
Format=asp\'?><?php exit(\'\'); ?>
Type=',
                '[GJK_Browscap_Version]
Version=6004
Released=Wed, 10 Jun 2015 07:48:33 +0000
Format=asp\'
Type=',
            ),
            array(
                '[GJK_Browscap_Version]
Version=6004
Released=Wed, 10 Jun 2015 07:48:33 +0000
Format=asp\'?><?php
Type=',
                '[GJK_Browscap_Version]
Version=6004
Released=Wed, 10 Jun 2015 07:48:33 +0000
Format=asp\'php
Type=',
            ),
            array(
                '[GJK_Browscap_Version]
Version=6004
Released=Wed, 10 Jun 2015 07:48:33 +0000
Format=asp\'?><?= exit(\'\'); ?>
Type=',
                '[GJK_Browscap_Version]
Version=6004
Released=Wed, 10 Jun 2015 07:48:33 +0000
Format=asp\'
Type=',
            ),
            array(
                '[GJK_Browscap_Version]
Version=6004
Released=Wed, 10 Jun 2015 07:48:33 +0000
Format=asp\'?><% exit(\'\'); %>
Type=',
                '[GJK_Browscap_Version]
Version=6004
Released=Wed, 10 Jun 2015 07:48:33 +0000
Format=asp\'
Type=',
            ),
        );
    }

    /**
     * @dataProvider dataCreateCache
     * @group        testParsing
     *
     * @param string $content
     */
    public function testCreateCache($content)
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('createCacheOldWay');
        $method->setAccessible(true);

        $varProp = $class->getProperty('_properties');
        $varProp->setAccessible(true);

        $varBrow = $class->getProperty('_browsers');
        $varBrow->setAccessible(true);

        $varUas = $class->getProperty('_userAgents');
        $varUas->setAccessible(true);

        $varPatt = $class->getProperty('_patterns');
        $varPatt->setAccessible(true);

        $varVersion = $class->getProperty('_source_version');
        $varVersion->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        $varProp->setValue($browscap, array());
        $varBrow->setValue($browscap, array());
        $varUas->setValue($browscap, array());
        $varPatt->setValue($browscap, array());
        $varVersion->setValue($browscap, 0);

        $method->invoke($browscap, $content, true);

        $properties = $varProp->getValue($browscap);
        $browsers   = $varBrow->getValue($browscap);
        $userAgents = $varUas->getValue($browscap);
        $patterns   = $varPatt->getValue($browscap);
        $version    = (string) $varVersion->getValue($browscap);

        $newMethod = $class->getMethod('createCacheNewWay');
        $newMethod->setAccessible(true);

        $varNewProp = $class->getProperty('_properties');
        $varNewProp->setAccessible(true);

        $varNewBrow = $class->getProperty('_browsers');
        $varNewBrow->setAccessible(true);

        $varNewUas = $class->getProperty('_userAgents');
        $varNewUas->setAccessible(true);

        $varNewPatt = $class->getProperty('_patterns');
        $varNewPatt->setAccessible(true);

        $varNewVersion = $class->getProperty('_source_version');
        $varNewVersion->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        $varNewProp->setValue($browscap, array());
        $varNewBrow->setValue($browscap, array());
        $varNewUas->setValue($browscap, array());
        $varNewPatt->setValue($browscap, array());
        $varNewVersion->setValue($browscap, 0);

        $newMethod->invoke($browscap, $content);

        $newVersion = (string) $varNewVersion->getValue($browscap);
        self::assertSame($version, $newVersion);

        $newProperties = $varNewProp->getValue($browscap);
        self::assertSame($properties, $newProperties);

        $newPatterns    = $varNewPatt->getValue($browscap);
        self::assertCount(count($patterns), $newPatterns);

        $newBrowsers = $varNewBrow->getValue($browscap);
        self::assertCount(count($browsers), $newBrowsers);

        $newUserAgents = $varNewUas->getValue($browscap);
        self::assertCount(count($userAgents), $newUserAgents);
    }

    /**
     * data provider for the testCreateCache function
     *
     * @return array[]
     */
    public function dataCreateCache()
    {
        $iterator = new \RecursiveDirectoryIterator('tests/data/');

        $fileContents = array();
        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            /** @var $file \SplFileInfo */
            if (!$file->isFile() || $file->getExtension() !== 'ini') {
                continue;
            }

            $fileContents[$file->getFilename()] = array(file_get_contents($file->getPathname()));
        }

        return $fileContents;
    }

    /**
     * @dataProvider dataBuildCache
     * @group        testCache
     *
     * @param array  $properties
     * @param array  $browsers
     * @param array  $userAgents
     * @param array  $patterns
     * @param string $version
     * @param string $expected
     */
    public function testBuildCache(
        array $properties,
        array $browsers,
        array $userAgents,
        array $patterns,
        $version,
        $expected
    ) {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_buildCache');
        $method->setAccessible(true);

        $varProp = $class->getProperty('_properties');
        $varProp->setAccessible(true);

        $varBrow = $class->getProperty('_browsers');
        $varBrow->setAccessible(true);

        $varUas = $class->getProperty('_userAgents');
        $varUas->setAccessible(true);

        $varPatt = $class->getProperty('_patterns');
        $varPatt->setAccessible(true);

        $varVersion = $class->getProperty('_source_version');
        $varVersion->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        $varProp->setValue($browscap, $properties);
        $varBrow->setValue($browscap, $browsers);
        $varUas->setValue($browscap, $userAgents);
        $varPatt->setValue($browscap, $patterns);
        $varVersion->setValue($browscap, $version);

        $return = $method->invoke($browscap);

        self::assertSame($expected, $return);
    }

    /**
     * data provider for the testCreateCache function
     *
     * @return array[]
     */
    public function dataBuildCache()
    {
        $data = array();
        for ($i = 1; $i <= 2; $i++) {
            // array $properties, array $browsers, array $userAgents, array $patterns, $version, $expected
            $data[$i] = array(
                'properties' => require 'tests/data/buildCache/' . $i . '.properties.php',
                'browsers'   => require 'tests/data/buildCache/' . $i . '.browsers.php',
                'userAgents' => require 'tests/data/buildCache/' . $i . '.userAgents.php',
                'patterns'   => require 'tests/data/buildCache/' . $i . '.patterns.php',
                'version'    => require 'tests/data/buildCache/' . $i . '.version.php',
                'expected'   => file_get_contents('tests/data/buildCache/' . $i . '.expected.php'),
            );
        }

        return $data;
    }
}
