<?php

namespace phpbrowscapTest;

use phpbrowscap\Browscap;
use ReflectionClass;

/**
 * Browscap.ini parsing class with caching and update capabilities
 * PHP version 5
 * Copyright (c) 2006-2012 Jonathan Stoppani
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
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
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testConstructorFails()
    {
        new Browscap();
    }

    /**
     * @expectedException \phpbrowscap\Exception
     * @expectedExceptionMessage You have to provide a path to read/store the browscap cache file
     */
    public function testConstructorFails2()
    {
        new Browscap(null);
    }

    /**
     *
     */
    public function testConstructorFails3()
    {
        $path = '/abc/test';

        $this->setExpectedException(
            '\\phpbrowscap\\Exception',
            'The cache path ' . $path . ' is invalid. Are you sure that it exists and that you have permission to access it?'
        )
        ;

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
     *
     */
    public function testArray2string()
    {
        $cacheDir = $this->createCacheDir();

        $class  = new ReflectionClass('\\phpbrowscap\\Browscap');
        $method = $class->getMethod('_array2string');
        $method->setAccessible(true);

        $browscap = new Browscap($cacheDir);

        $result = 'array(' . "\n" . '\'a\'=>1,' . "\n" . '\'b\'=>\'abc\',' . "\n" . '1=>\'cde\',' . "\n" . '\'def\',' . "\n" . '\'a:3:{i:0;s:3:"abc";i:1;i:1;i:2;i:2;}\',' . "\n" . "\n" . ')';

        // "tempnam" did not work with VFSStream for tests
        $tmpFile = $cacheDir . '/temp_test_' . md5(time());

        if (false == ($fileRes = fopen($tmpFile, 'w+'))) {
            throw new \RuntimeException(sprintf('Unable to create temporary file "%s"', $tmpFile));
        }

        self::assertTrue(
            $method->invoke(
                $browscap,
                array('a' => 1, 'b' => 'abc', '1.0' => 'cde', 1 => 'def', 2 => array('abc', 1, 2)),
                $fileRes
            )
        )
        ;

        fclose($fileRes);

        self::assertSame($result, file_get_contents($tmpFile));

        unlink($tmpFile);
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

        $expected = '@^Mozilla/.\.0 \(compatible; Ask Jeeves/Teoma.*\)$@';

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
     * @dataProvider dataCtreateCache
     * @group        testParsing
     */
    public function testCreateCacheOldWay($content)
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

        $browscap = new Browscap($cacheDir);

        $varProp->setValue($browscap, array());
        $varBrow->setValue($browscap, array());
        $varUas->setValue($browscap, array());
        $varPatt->setValue($browscap, array());

        $method->invoke($browscap, $content, true);

        $properties = $varProp->getValue($browscap);
        self::assertCount(48, $properties);
        self::assertSame(
            array (
                0 => 'browser_name',
                1 => 'browser_name_regex',
                2 => 'browser_name_pattern',
                3 => 'Parent',
                4 => 'Comment',
                5 => 'Browser',
                6 => 'Browser_Type',
                7 => 'Browser_Bits',
                8 => 'Browser_Maker',
                9 => 'Browser_Modus',
                10 => 'Version',
                11 => 'MajorVer',
                12 => 'MinorVer',
                13 => 'Platform',
                14 => 'Platform_Version',
                15 => 'Platform_Description',
                16 => 'Platform_Bits',
                17 => 'Platform_Maker',
                18 => 'Alpha',
                19 => 'Beta',
                20 => 'Win16',
                21 => 'Win32',
                22 => 'Win64',
                23 => 'Frames',
                24 => 'IFrames',
                25 => 'Tables',
                26 => 'Cookies',
                27 => 'BackgroundSounds',
                28 => 'JavaScript',
                29 => 'VBScript',
                30 => 'JavaApplets',
                31 => 'ActiveXControls',
                32 => 'isMobileDevice',
                33 => 'isTablet',
                34 => 'isSyndicationReader',
                35 => 'Crawler',
                36 => 'CssVersion',
                37 => 'AolVersion',
                38 => 'Device_Name',
                39 => 'Device_Maker',
                40 => 'Device_Type',
                41 => 'Device_Pointing_Method',
                42 => 'Device_Code_Name',
                43 => 'Device_Brand_Name',
                44 => 'RenderingEngine_Name',
                45 => 'RenderingEngine_Version',
                46 => 'RenderingEngine_Description',
                47 => 'RenderingEngine_Maker',
            ),
            $properties
        )
        ;

        $browsers = $varBrow->getValue($browscap);
        self::assertCount(160, $browsers);
        self::assertSame(
            array (
                0 =>
                    array (
                        4 => 'DefaultProperties',
                        5 => 'DefaultProperties',
                        6 => 'unknown',
                        7 => '0',
                        8 => 'unknown',
                        9 => 'unknown',
                        10 => '0.0',
                        11 => '0',
                        12 => '0',
                        13 => 'unknown',
                        14 => 'unknown',
                        15 => 'unknown',
                        16 => '0',
                        17 => 'unknown',
                        18 => 'false',
                        19 => 'false',
                        20 => 'false',
                        21 => 'false',
                        22 => 'false',
                        23 => 'false',
                        24 => 'false',
                        25 => 'false',
                        26 => 'false',
                        27 => 'false',
                        28 => 'false',
                        29 => 'false',
                        30 => 'false',
                        31 => 'false',
                        32 => 'false',
                        33 => 'false',
                        34 => 'false',
                        35 => 'false',
                        36 => '0',
                        37 => '0',
                        38 => 'unknown',
                        39 => 'unknown',
                        40 => 'unknown',
                        41 => 'unknown',
                        42 => 'unknown',
                        43 => 'unknown',
                        44 => 'unknown',
                        45 => 'unknown',
                        46 => 'unknown',
                        47 => 'unknown',
                    ),
                1 =>
                    array (
                        3 => 0,
                        4 => 'Ask',
                        5 => 'Ask',
                        6 => 'Bot/Crawler',
                        8 => 'Ask.com',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                2 =>
                    array (
                        3 => 1,
                        5 => 'Teoma',
                    ),
                3 =>
                    array (
                        3 => 1,
                        5 => 'AskJeeves',
                    ),
                4 =>
                    array (
                        3 => 0,
                        4 => '360Spider',
                        5 => '360Spider',
                        6 => 'Bot/Crawler',
                        8 => 'so.360.cn',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                5 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win2000',
                        14 => '5.0',
                        15 => 'Windows 2000',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                6 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win2000',
                        14 => '5.01',
                        15 => 'Windows 2000',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                7 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win2000',
                        14 => '5.0',
                        15 => 'Windows 2000',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                8 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                9 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                10 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                11 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                12 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                13 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                14 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                15 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                16 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                17 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                18 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                19 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                20 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                21 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                22 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                23 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                24 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                25 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                26 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                27 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                28 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                29 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                30 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                31 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                32 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                33 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                34 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                35 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                36 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                37 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                38 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                39 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                40 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                41 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                42 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                43 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                44 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                45 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                46 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                47 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                48 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                49 =>
                    array (
                        3 => 0,
                        4 => '80Legs',
                        5 => '80Legs',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                50 =>
                    array (
                        3 => 49,
                    ),
                51 =>
                    array (
                        3 => 0,
                        4 => 'AhrefsBot',
                        5 => 'AhrefsBot',
                        6 => 'Bot/Crawler',
                        8 => 'Ahrefs Pte Ltd',
                        35 => 'true',
                    ),
                52 =>
                    array (
                        3 => 51,
                        10 => '3.1',
                        11 => '3',
                        12 => '1',
                    ),
                53 =>
                    array (
                        3 => 51,
                        10 => '4.0',
                        11 => '4',
                    ),
                54 =>
                    array (
                        3 => 51,
                        10 => '5.0',
                        11 => '5',
                    ),
                55 =>
                    array (
                        3 => 51,
                    ),
                56 =>
                    array (
                        3 => 0,
                        4 => 'Adbeat',
                        5 => 'Adbeat Bot',
                        6 => 'Bot/Crawler',
                        8 => 'adbeat.com',
                        35 => 'true',
                    ),
                57 =>
                    array (
                        3 => 56,
                        7 => '32',
                        13 => 'Linux',
                        15 => 'Linux',
                        16 => '32',
                        17 => 'Linux Foundation',
                        38 => 'Linux Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Linux Desktop',
                    ),
                58 =>
                    array (
                        3 => 56,
                    ),
                59 =>
                    array (
                        3 => 0,
                        4 => 'NikiBot',
                        5 => 'NikiBot',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                60 =>
                    array (
                        3 => 59,
                    ),
                61 =>
                    array (
                        3 => 0,
                        4 => 'GrapeshotCrawler',
                        5 => 'GrapeshotCrawler',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                62 =>
                    array (
                        3 => 61,
                        10 => '2.0',
                        11 => '2',
                    ),
                63 =>
                    array (
                        3 => 61,
                    ),
                64 =>
                    array (
                        3 => 61,
                        5 => 'grapeFX',
                        10 => '0.9',
                        12 => '9',
                    ),
                65 =>
                    array (
                        3 => 61,
                        5 => 'grapeFX',
                    ),
                66 =>
                    array (
                        3 => 0,
                        4 => 'Anonymizied',
                        5 => 'Anonymizied',
                        6 => 'Bot/Crawler',
                        35 => 'true',
                    ),
                67 =>
                    array (
                        3 => 66,
                    ),
                68 =>
                    array (
                        3 => 66,
                    ),
                69 =>
                    array (
                        3 => 66,
                    ),
                70 =>
                    array (
                        3 => 66,
                    ),
                71 =>
                    array (
                        3 => 66,
                    ),
                72 =>
                    array (
                        3 => 66,
                    ),
                73 =>
                    array (
                        3 => 66,
                    ),
                74 =>
                    array (
                        3 => 66,
                    ),
                75 =>
                    array (
                        3 => 0,
                        4 => 'Yandex',
                        5 => 'Yandex',
                        6 => 'Bot/Crawler',
                        8 => 'Yandex',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                76 =>
                    array (
                        3 => 75,
                        5 => 'YandexAddURL',
                    ),
                77 =>
                    array (
                        3 => 75,
                        5 => 'YandexBlogs',
                    ),
                78 =>
                    array (
                        3 => 75,
                        5 => 'Yandex MirrorDetector',
                    ),
                79 =>
                    array (
                        3 => 75,
                        5 => 'YandexCatalog',
                    ),
                80 =>
                    array (
                        3 => 75,
                        5 => 'YandexDirect-Dyatel',
                    ),
                81 =>
                    array (
                        3 => 75,
                        5 => 'YandexFavicons',
                    ),
                82 =>
                    array (
                        3 => 75,
                        5 => 'YandexImageResizer',
                    ),
                83 =>
                    array (
                        3 => 75,
                        5 => 'YandexImages',
                    ),
                84 =>
                    array (
                        3 => 75,
                        5 => 'YandexMedia',
                    ),
                85 =>
                    array (
                        3 => 75,
                        5 => 'YandexMetrika',
                    ),
                86 =>
                    array (
                        3 => 75,
                        5 => 'YandexNews',
                    ),
                87 =>
                    array (
                        3 => 75,
                        5 => 'YandexVideo',
                    ),
                88 =>
                    array (
                        3 => 75,
                        5 => 'YandexWebmaster',
                    ),
                89 =>
                    array (
                        3 => 75,
                        5 => 'YandexZakladki',
                    ),
                90 =>
                    array (
                        3 => 75,
                    ),
                91 =>
                    array (
                        3 => 75,
                    ),
                92 =>
                    array (
                        3 => 75,
                    ),
                93 =>
                    array (
                        3 => 75,
                    ),
                94 =>
                    array (
                        3 => 75,
                    ),
                95 =>
                    array (
                        3 => 75,
                    ),
                96 =>
                    array (
                        3 => 75,
                    ),
                97 =>
                    array (
                        3 => 75,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                    ),
                98 =>
                    array (
                        3 => 75,
                        5 => 'YandexAddURL',
                    ),
                99 =>
                    array (
                        3 => 75,
                        5 => 'YandexCatalog',
                    ),
                100 =>
                    array (
                        3 => 75,
                        5 => 'YandexDirect-Dyatel',
                    ),
                101 =>
                    array (
                        3 => 75,
                        5 => 'YandexFavicons',
                    ),
                102 =>
                    array (
                        3 => 75,
                        5 => 'YandexImageResizer',
                    ),
                103 =>
                    array (
                        3 => 75,
                        5 => 'YandexImages',
                    ),
                104 =>
                    array (
                        3 => 75,
                        5 => 'YandexMedia',
                    ),
                105 =>
                    array (
                        3 => 75,
                        5 => 'YandexMetrika',
                    ),
                106 =>
                    array (
                        3 => 75,
                        5 => 'YandexNews',
                    ),
                107 =>
                    array (
                        3 => 75,
                        5 => 'YandexVideo',
                    ),
                108 =>
                    array (
                        3 => 0,
                        4 => 'Apache Bench',
                        5 => 'Apache Bench',
                        6 => 'Bot/Crawler',
                        8 => 'Apache Foundation',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                109 =>
                    array (
                        3 => 108,
                    ),
                110 =>
                    array (
                        3 => 0,
                        4 => 'YandexBot',
                        5 => 'YandexBot',
                        6 => 'Bot/Crawler',
                        8 => 'Yandex',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                111 =>
                    array (
                        3 => 110,
                        10 => '3.0',
                        11 => '3',
                    ),
                112 =>
                    array (
                        3 => 110,
                    ),
                113 =>
                    array (
                        3 => 0,
                        4 => 'Goldfire Server',
                        5 => 'Goldfire Server',
                        6 => 'Bot/Crawler',
                        8 => 'Invention Machine Corporation',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                114 =>
                    array (
                        3 => 113,
                    ),
                115 =>
                    array (
                        3 => 0,
                        4 => 'ArchitextSpider',
                        5 => 'ArchitextSpider',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                116 =>
                    array (
                        3 => 115,
                    ),
                117 =>
                    array (
                        3 => 0,
                        4 => 'Become',
                        5 => 'Become',
                        6 => 'Bot/Crawler',
                        34 => 'true',
                        35 => 'true',
                    ),
                118 =>
                    array (
                        3 => 117,
                        5 => 'BecomeBot',
                    ),
                119 =>
                    array (
                        3 => 117,
                        5 => 'BecomeBot',
                    ),
                120 =>
                    array (
                        3 => 117,
                        5 => 'MonkeyCrawl',
                    ),
                121 =>
                    array (
                        3 => 117,
                        5 => 'BecomeJPBot',
                    ),
                122 =>
                    array (
                        3 => 117,
                        5 => 'BecomeJPBot',
                    ),
                123 =>
                    array (
                        3 => 0,
                        4 => 'Convera',
                        5 => 'Convera',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                124 =>
                    array (
                        3 => 123,
                        5 => 'ConveraCrawler',
                    ),
                125 =>
                    array (
                        3 => 123,
                        5 => 'ConveraMultiMediaCrawler',
                        10 => '0.1',
                        12 => '1',
                    ),
                126 =>
                    array (
                        3 => 123,
                        5 => 'CrawlConvera',
                    ),
                127 =>
                    array (
                        3 => 123,
                        10 => '0.4',
                        12 => '4',
                    ),
                128 =>
                    array (
                        3 => 123,
                        10 => '0.5',
                        12 => '5',
                    ),
                129 =>
                    array (
                        3 => 123,
                        10 => '0.6',
                        12 => '6',
                    ),
                130 =>
                    array (
                        3 => 123,
                        10 => '0.7',
                        12 => '7',
                    ),
                131 =>
                    array (
                        3 => 123,
                        10 => '0.8',
                        12 => '8',
                    ),
                132 =>
                    array (
                        3 => 123,
                        10 => '0.9',
                        12 => '9',
                    ),
                133 =>
                    array (
                        3 => 0,
                        4 => 'Best of the Web',
                        5 => 'Best of the Web',
                        6 => 'Bot/Crawler',
                        8 => 'botw.org',
                        23 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                134 =>
                    array (
                        3 => 133,
                        5 => 'BOTW Feed Grabber',
                        34 => 'true',
                    ),
                135 =>
                    array (
                        3 => 133,
                        5 => 'BOTW Feed Grabber',
                        34 => 'true',
                    ),
                136 =>
                    array (
                        3 => 133,
                        5 => 'BOTW Spider',
                    ),
                137 =>
                    array (
                        3 => 133,
                        5 => 'BOTW Spider',
                    ),
                138 =>
                    array (
                        3 => 0,
                        4 => 'ContextAd Bot',
                        5 => 'ContextAd Bot',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                139 =>
                    array (
                        3 => 138,
                        10 => '1.0',
                        11 => '1',
                    ),
                140 =>
                    array (
                        3 => 138,
                    ),
                141 =>
                    array (
                        3 => 0,
                        4 => 'Java Standard Library',
                        5 => 'Java Standard Library',
                        6 => 'Bot/Crawler',
                        8 => 'Oracle',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                142 =>
                    array (
                        3 => 141,
                        10 => '1.4',
                        11 => '1',
                        12 => '4',
                    ),
                143 =>
                    array (
                        3 => 141,
                        10 => '1.5',
                        11 => '1',
                        12 => '5',
                    ),
                144 =>
                    array (
                        3 => 141,
                        10 => '1.6',
                        11 => '1',
                        12 => '6',
                    ),
                145 =>
                    array (
                        3 => 141,
                        10 => '1.7',
                        11 => '1',
                        12 => '7',
                    ),
                146 =>
                    array (
                        3 => 141,
                        10 => '1.17',
                        11 => '1',
                        12 => '17',
                    ),
                147 =>
                    array (
                        3 => 141,
                    ),
                148 =>
                    array (
                        3 => 0,
                        4 => 'DotBot',
                        5 => 'DotBot',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                149 =>
                    array (
                        3 => 148,
                    ),
                150 =>
                    array (
                        3 => 148,
                    ),
                151 =>
                    array (
                        3 => 148,
                        10 => '1.1',
                        11 => '1',
                        12 => '1',
                    ),
                152 =>
                    array (
                        3 => 148,
                    ),
                153 =>
                    array (
                        3 => 0,
                        4 => 'Bitlybot',
                        5 => 'BitlyBot',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                154 =>
                    array (
                        3 => 153,
                        10 => '2.0',
                        11 => '2',
                    ),
                155 =>
                    array (
                        3 => 153,
                    ),
                156 =>
                    array (
                        3 => 0,
                        4 => 'Entireweb',
                        5 => 'Entireweb',
                        6 => 'Bot/Crawler',
                        8 => 'Entireweb Sweden AB',
                        23 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                157 =>
                    array (
                        3 => 156,
                    ),
                158 =>
                    array (
                        3 => 156,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                    ),
                159 =>
                    array (
                        3 => 156,
                    ),
            ),
            $browsers
        )
        ;

        $userAgents = $varUas->getValue($browscap);
        self::assertCount(22, $userAgents);
        self::assertSame(
            array (
                '0.0' => 'DefaultProperties',
                '1.0' => 'Ask',
                '4.0' => '360Spider',
                '49.0' => '80Legs',
                '51.0' => 'AhrefsBot',
                '56.0' => 'Adbeat',
                '59.0' => 'NikiBot',
                '61.0' => 'GrapeshotCrawler',
                '66.0' => 'Anonymizied',
                '75.0' => 'Yandex',
                '108.0' => 'Apache Bench',
                '110.0' => 'YandexBot',
                '113.0' => 'Goldfire Server',
                '115.0' => 'ArchitextSpider',
                '117.0' => 'Become',
                '123.0' => 'Convera',
                '133.0' => 'Best of the Web',
                '138.0' => 'ContextAd Bot',
                '141.0' => 'Java Standard Library',
                '148.0' => 'DotBot',
                '153.0' => 'bitlybot',
                '156.0' => 'Entireweb',
            ),
            $userAgents
        )
        ;

        $patterns = $varPatt->getValue($browscap);
        self::assertCount(98, $patterns);
        self::assertSame(
            array (
                '@^Mozilla/.\\.0 \\(compatible; Ask Jeeves/Teoma.*\\)$@' => 2,
                '@^Mozilla/2\\.0 \\(compatible; Ask Jeeves\\)$@' => 3,
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT 5\\.0; .*WOW64.*Trident/4\\.0.*\\).* 360Spider$@' => 5,
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT 5\\.01.*Trident/4\\.0.*\\).* 360Spider$@' => 6,
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT (\\d)\\.(\\d).*Trident/4\\.0.*\\).* 360Spider$@' =>
                    array (
                        '@5|0' => 7,
                        '@5|1' => 10,
                        '@5|2' => 13,
                        '@6|0' => 17,
                        '@6|1' => 20,
                        '@6|2' => 23,
                    ),
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT (\\d)\\.(\\d);.*Win64. x64.*Trident/4\\.0.*\\).* 360Spider$@' =>
                    array (
                        '@5|1' => 8,
                        '@5|2' => 11,
                        '@6|0' => 15,
                    ),
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT (\\d)\\.(\\d).*WOW64.*Trident/4\\.0.*\\).* 360Spider$@' =>
                    array (
                        '@5|1' => 9,
                        '@5|2' => 12,
                        '@6|0' => 16,
                        '@6|1' => 19,
                        '@6|2' => 22,
                    ),
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT 5\\.2;.*Win64.*Trident/4\\.0.*\\).* 360Spider$@' => 14,
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT 6\\.(\\d).*Win64. x64.*Trident/4\\.0.*\\).* 360Spider$@' =>
                    array (
                        '@1' => 18,
                        '@2' => 21,
                    ),
                '@^Mozilla/5\\.0 \\(compatible; MSIE 9\\.0.*; .*Windows NT (\\d)\\.(\\d).*WOW64.*Trident/5\\.0.* 360Spider$@' =>
                    array (
                        '@5|1' => 24,
                        '@5|2' => 26,
                        '@6|0' => 28,
                        '@6|1' => 31,
                        '@6|2' => 34,
                    ),
                '@^Mozilla/5\\.0 \\(compatible; MSIE 9\\.0.*; .*Windows NT (\\d)\\.(\\d).*Trident/5\\.0.* 360Spider$@' =>
                    array (
                        '@5|1' => 25,
                        '@5|2' => 27,
                        '@6|0' => 29,
                        '@6|1' => 32,
                        '@6|2' => 35,
                    ),
                '@^Mozilla/5\\.0 \\(compatible; MSIE 9\\.0.*; .*Windows NT 6\\.(\\d).*Win64. x64.*Trident/5\\.0.* 360Spider$@' =>
                    array (
                        '@1' => 30,
                        '@2' => 33,
                    ),
                '@^Mozilla/5\\.0 \\(.*Windows NT 5\\.1.*\\)  Firefox/.*; 360Spider.*$@' => 36,
                '@^Mozilla/5\\.0 \\(.*Windows NT (\\d)\\.(\\d).*WOW64.*\\) AppleWebKit/.* \\(KHTML, like Gecko\\) Chrome/.* Safari/.*; 360Spider.*$@' =>
                    array (
                        '@5|1' => 37,
                        '@5|2' => 39,
                        '@6|0' => 41,
                        '@6|1' => 44,
                        '@6|2' => 47,
                    ),
                '@^Mozilla/5\\.0 \\(.*Windows NT (\\d)\\.(\\d).*\\) AppleWebKit/.* \\(KHTML, like Gecko\\) Chrome/.* Safari/.*; 360Spider.*$@' =>
                    array (
                        '@5|1' => 38,
                        '@5|2' => 40,
                        '@6|0' => 42,
                        '@6|1' => 45,
                        '@6|2' => 48,
                    ),
                '@^Mozilla/5\\.0 \\(.*Windows NT 6\\.(\\d).*Win64. x64.*\\) AppleWebKit/.* \\(KHTML, like Gecko\\) Chrome/.* Safari/.*; 360Spider.*$@' =>
                    array (
                        '@1' => 43,
                        '@2' => 46,
                    ),
                '@^Mozilla/5\\.0 \\(compatible; .*; http\\://www\\.80legs\\.com/.*\\) Gecko/.*$@' => 50,
                '@^Mozilla/5\\.0 \\(compatible; AhrefsBot/(\\d)\\.(\\d).*$@' =>
                    array (
                        '@3|1' => 52,
                        '@4|0' => 53,
                        '@5|0' => 54,
                    ),
                '@^Mozilla/5\\.0 \\(compatible; AhrefsBot/.*$@' => 55,
                '@^Mozilla/5\\.0 \\(.*Linux x86.*\\) adbeat\\.com.* Gecko/.* Firefox/.*AppleWebKit/.*Safari/.*$@' => 57,
                '@^adbeat_bot.*$@' => 58,
                '@^niki\\-bot$@' => 60,
                '@^Mozilla/5\\.0 \\(compatible; GrapeshotCrawler/2\\.0; \\+http\\://www\\.grapeshot\\.co\\.uk/crawler\\.php\\)$@' => 62,
                '@^Mozilla/5\\.0 \\(compatible; GrapeshotCrawler/.*; \\+http\\://www\\.grapeshot\\.co\\.uk/crawler\\.php\\)$@' => 63,
                '@^Mozilla/5\\.0 \\(compatible; grapeFX/0\\.9; crawler\\@grapeshot\\.co\\.uk$@' => 64,
                '@^Mozilla/5\\.0 \\(compatible; grapeFX/.*; crawler\\@grapeshot\\.co\\.uk$@' => 65,
                '@^Anonymisiert durch AlMiSoft Browser\\-Maulkorb \\(Anonymisier.*$@' => 67,
                '@^Anonymisiert.*$@' => 68,
                '@^Anonymizer/.*$@' => 69,
                '@^Anonymizied.*$@' => 70,
                '@^Anonymous.*$@' => 71,
                '@^Anonymous/.*$@' => 72,
                '@^http\\://Anonymouse\\.org/.*$@' => 73,
                '@^Mozilla/5\\.0 \\(Randomized by FreeSafeIP.*$@' => 74,
                '@^Mozilla/5\\.0 \\(compatible; YandexAddurl/.*\\)$@' => 76,
                '@^Mozilla/5\\.0 \\(compatible; YandexBlogs/.*\\)$@' => 77,
                '@^Mozilla/5\\.0 \\(compatible; YandexBot/.*; MirrorDetector.*\\)$@' => 78,
                '@^Mozilla/5\\.0 \\(compatible; YandexCatalog/.*\\)$@' => 79,
                '@^Mozilla/5\\.0 \\(compatible; YandexDirect/.*\\)$@' => 80,
                '@^Mozilla/5\\.0 \\(compatible; YandexFavicons/.*\\)$@' => 81,
                '@^Mozilla/5\\.0 \\(compatible; YandexImageResizer/.*\\)$@' => 82,
                '@^Mozilla/5\\.0 \\(compatible; YandexImages/.*\\)$@' => 83,
                '@^Mozilla/5\\.0 \\(compatible; YandexMedia/.*\\)$@' => 84,
                '@^Mozilla/5\\.0 \\(compatible; YandexMetrika/.*\\)$@' => 85,
                '@^Mozilla/5\\.0 \\(compatible; YandexNews/.*\\)$@' => 86,
                '@^Mozilla/5\\.0 \\(compatible; YandexVideo/.*\\)$@' => 87,
                '@^Mozilla/5\\.0 \\(compatible; YandexWebmaster/.*\\)$@' => 88,
                '@^Mozilla/5\\.0 \\(compatible; YandexZakladki/.*\\)$@' => 89,
                '@^Yandex/1\\.01\\.001 \\(compatible; Win16; .*\\)$@' => 90,
                '@^Mozilla/4\\.0 \\(.*compatible.*;.*MSIE 5\\.0; YANDEX\\)$@' => 91,
                '@^Mozilla/5\\.0 \\(compatible; YandexBot/.*; MirrorDetector\\)$@' => 92,
                '@^Mozilla/5\\.0 \\(compatible; YandexZakladki/.*; Dyatel; \\+http\\://yandex\\.com/bots\\)$@' => 93,
                '@^YaDirectBot/.*$@' => 94,
                '@^Yandex/.*$@' => 95,
                '@^YandexSomething/.*$@' => 96,
                '@^Mozilla/5\\.0 \\(Windows; .; Windows NT 5\\.2; en\\-US; rv\\:1\\.9\\) Gecko VisualParser/3\\.0$@' => 97,
                '@^Mozilla/5\\.0 \\(compatible; YandexAddurl/.*$@' => 98,
                '@^Mozilla/5\\.0 \\(compatible; YandexCatalog/.*$@' => 99,
                '@^Mozilla/5\\.0 \\(compatible; YandexDirect/.*$@' => 100,
                '@^Mozilla/5\\.0 \\(compatible; YandexFavicons/.*$@' => 101,
                '@^Mozilla/5\\.0 \\(compatible; YandexImageResizer/.*$@' => 102,
                '@^Mozilla/5\\.0 \\(compatible; YandexImages/.*$@' => 103,
                '@^Mozilla/5\\.0 \\(compatible; YandexMedia/.*$@' => 104,
                '@^Mozilla/5\\.0 \\(compatible; YandexMetrika/.*$@' => 105,
                '@^Mozilla/5\\.0 \\(compatible; YandexNews/.*$@' => 106,
                '@^Mozilla/5\\.0 \\(compatible; YandexVideo/.*$@' => 107,
                '@^ApacheBench/.*$@' => 109,
                '@^Mozilla/5\\.0 \\(compatible; YandexBot/3\\.0.*$@' => 111,
                '@^Mozilla/5\\.0 \\(compatible; YandexBot/.*$@' => 112,
                '@^Goldfire Server.*$@' => 114,
                '@^ArchitextSpider.*$@' => 116,
                '@^.*BecomeBot/.*$@' => 118,
                '@^.*BecomeBot\\@exava\\.com.*$@' => 119,
                '@^MonkeyCrawl/.*$@' => 120,
                '@^Mozilla/5\\.0 \\(compatible; BecomeJPBot/2\\.3; .*\\)$@' => 121,
                '@^Mozilla/5\\.0 \\(compatible; BecomeJPBot/2\\.3.*\\)$@' => 122,
                '@^ConveraCrawler/.*$@' => 124,
                '@^ConveraMultiMediaCrawler/0\\.1.*$@' => 125,
                '@^CrawlConvera.*$@' => 126,
                '@^ConveraCrawler/0\\.(\\d).*$@' =>
                    array (
                        '@4' => 127,
                        '@5' => 128,
                        '@6' => 129,
                        '@7' => 130,
                        '@8' => 131,
                        '@9' => 132,
                    ),
                '@^Mozilla/4\\.0 \\(compatible; BOTW Feed Grabber.*http\\://botw\\.org\\)$@' => 134,
                '@^Mozilla/4\\.0 \\(compatible; BOTW Feed Grabber; .*http\\://botw\\.org\\)$@' => 135,
                '@^Mozilla/4\\.0 \\(compatible; BOTW Spider; .*http\\://botw\\.org\\)$@' => 136,
                '@^Mozilla/4\\.0 \\(compatible; BOTW Spider.*http\\://botw\\.org\\)$@' => 137,
                '@^ContextAd Bot 1\\.0.*$@' => 139,
                '@^ContextAd Bot.*$@' => 140,
                '@^Java/1\\.(\\d).*$@' =>
                    array (
                        '@4' => 142,
                        '@5' => 143,
                        '@6' => 144,
                        '@7' => 145,
                    ),
                '@^Java/1\\.17.*$@' => 146,
                '@^Java/.*$@' => 147,
                '@^DotBot/.* \\(http\\://www\\.dotnetdotcom\\.org/.*\\)$@' => 149,
                '@^Mozilla/5\\.0 \\(compatible; DotBot/.*; http\\://www\\.dotnetdotcom\\.org/.*\\)$@' => 150,
                '@^Mozilla/5\\.0 \\(compatible; DotBot/1\\.1; http\\://www\\.opensiteexplorer\\.org/dotbot.*\\)$@' => 151,
                '@^Mozilla/5\\.0 \\(compatible; DotBot/.*; http\\://www\\.opensiteexplorer\\.org/dotbot.*\\)$@' => 152,
                '@^bitlybot/2\\..*$@' => 154,
                '@^bitlybot.*$@' => 155,
                '@^Mozilla/5\\.0 \\(compatible; Speedy Spider; .*$@' => 157,
                '@^Mozilla/5\\.0 \\(Windows; .; Windows NT 5\\.1; .*\\) Speedy Spider .*$@' => 158,
                '@^Speedy Spider .*$@' => 159,
            ),
            $patterns
        );

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

        $browscap = new Browscap($cacheDir);

        $varNewProp->setValue($browscap, array());
        $varNewBrow->setValue($browscap, array());
        $varNewUas->setValue($browscap, array());
        $varNewPatt->setValue($browscap, array());

        $newMethod->invoke($browscap, $content);

        $newProperties = $varProp->getValue($browscap);
        self::assertCount(48, $newProperties);
        self::assertSame(
            array (
                0 => 'browser_name',
                1 => 'browser_name_regex',
                2 => 'browser_name_pattern',
                3 => 'Parent',
                4 => 'Comment',
                5 => 'Browser',
                6 => 'Browser_Type',
                7 => 'Browser_Bits',
                8 => 'Browser_Maker',
                9 => 'Browser_Modus',
                10 => 'Version',
                11 => 'MajorVer',
                12 => 'MinorVer',
                13 => 'Platform',
                14 => 'Platform_Version',
                15 => 'Platform_Description',
                16 => 'Platform_Bits',
                17 => 'Platform_Maker',
                18 => 'Alpha',
                19 => 'Beta',
                20 => 'Win16',
                21 => 'Win32',
                22 => 'Win64',
                23 => 'Frames',
                24 => 'IFrames',
                25 => 'Tables',
                26 => 'Cookies',
                27 => 'BackgroundSounds',
                28 => 'JavaScript',
                29 => 'VBScript',
                30 => 'JavaApplets',
                31 => 'ActiveXControls',
                32 => 'isMobileDevice',
                33 => 'isTablet',
                34 => 'isSyndicationReader',
                35 => 'Crawler',
                36 => 'CssVersion',
                37 => 'AolVersion',
                38 => 'Device_Name',
                39 => 'Device_Maker',
                40 => 'Device_Type',
                41 => 'Device_Pointing_Method',
                42 => 'Device_Code_Name',
                43 => 'Device_Brand_Name',
                44 => 'RenderingEngine_Name',
                45 => 'RenderingEngine_Version',
                46 => 'RenderingEngine_Description',
                47 => 'RenderingEngine_Maker',
            ),
            $newProperties
        )
        ;
        self::assertEquals($properties, $newProperties);

        $newBrowsers = $varBrow->getValue($browscap);
        self::assertCount(160, $newBrowsers);
        self::assertSame(
            array (
                0 =>
                    array (
                        4 => 'DefaultProperties',
                        5 => 'DefaultProperties',
                        6 => 'unknown',
                        7 => '0',
                        8 => 'unknown',
                        9 => 'unknown',
                        10 => '0.0',
                        11 => '0',
                        12 => '0',
                        13 => 'unknown',
                        14 => 'unknown',
                        15 => 'unknown',
                        16 => '0',
                        17 => 'unknown',
                        18 => 'false',
                        19 => 'false',
                        20 => 'false',
                        21 => 'false',
                        22 => 'false',
                        23 => 'false',
                        24 => 'false',
                        25 => 'false',
                        26 => 'false',
                        27 => 'false',
                        28 => 'false',
                        29 => 'false',
                        30 => 'false',
                        31 => 'false',
                        32 => 'false',
                        33 => 'false',
                        34 => 'false',
                        35 => 'false',
                        36 => '0',
                        37 => '0',
                        38 => 'unknown',
                        39 => 'unknown',
                        40 => 'unknown',
                        41 => 'unknown',
                        42 => 'unknown',
                        43 => 'unknown',
                        44 => 'unknown',
                        45 => 'unknown',
                        46 => 'unknown',
                        47 => 'unknown',
                    ),
                1 =>
                    array (
                        3 => 0,
                        4 => 'Ask',
                        5 => 'Ask',
                        6 => 'Bot/Crawler',
                        8 => 'Ask.com',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                2 =>
                    array (
                        3 => 1,
                        5 => 'Teoma',
                    ),
                3 =>
                    array (
                        3 => 1,
                        5 => 'AskJeeves',
                    ),
                4 =>
                    array (
                        3 => 0,
                        4 => '360Spider',
                        5 => '360Spider',
                        6 => 'Bot/Crawler',
                        8 => 'so.360.cn',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                5 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win2000',
                        14 => '5.0',
                        15 => 'Windows 2000',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                6 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win2000',
                        14 => '5.01',
                        15 => 'Windows 2000',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                7 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win2000',
                        14 => '5.0',
                        15 => 'Windows 2000',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                8 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                9 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                10 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                11 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                12 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                13 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                14 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                15 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                16 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                17 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                18 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                19 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                20 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                21 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                22 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                23 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                24 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                25 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                26 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                27 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                28 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                29 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                30 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                31 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                32 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                33 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                34 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                35 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                36 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                    ),
                37 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                38 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                39 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                40 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                41 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                42 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'WinVista',
                        14 => '6.0',
                        15 => 'Windows Vista',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                43 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                44 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                45 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win7',
                        14 => '6.1',
                        15 => 'Windows 7',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                46 =>
                    array (
                        3 => 4,
                        7 => '64',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                47 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '64',
                        17 => 'Microsoft Corporation',
                        22 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                48 =>
                    array (
                        3 => 4,
                        7 => '32',
                        13 => 'Win8',
                        14 => '6.2',
                        15 => 'Windows 8',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                        30 => 'true',
                        38 => 'Windows Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Windows Desktop',
                        44 => 'WebKit',
                        46 => 'For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3.',
                        47 => 'Apple Inc',
                    ),
                49 =>
                    array (
                        3 => 0,
                        4 => '80Legs',
                        5 => '80Legs',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                50 =>
                    array (
                        3 => 49,
                    ),
                51 =>
                    array (
                        3 => 0,
                        4 => 'AhrefsBot',
                        5 => 'AhrefsBot',
                        6 => 'Bot/Crawler',
                        8 => 'Ahrefs Pte Ltd',
                        35 => 'true',
                    ),
                52 =>
                    array (
                        3 => 51,
                        10 => '3.1',
                        11 => '3',
                        12 => '1',
                    ),
                53 =>
                    array (
                        3 => 51,
                        10 => '4.0',
                        11 => '4',
                    ),
                54 =>
                    array (
                        3 => 51,
                        10 => '5.0',
                        11 => '5',
                    ),
                55 =>
                    array (
                        3 => 51,
                    ),
                56 =>
                    array (
                        3 => 0,
                        4 => 'Adbeat',
                        5 => 'Adbeat Bot',
                        6 => 'Bot/Crawler',
                        8 => 'adbeat.com',
                        35 => 'true',
                    ),
                57 =>
                    array (
                        3 => 56,
                        7 => '32',
                        13 => 'Linux',
                        15 => 'Linux',
                        16 => '32',
                        17 => 'Linux Foundation',
                        38 => 'Linux Desktop',
                        39 => 'Various',
                        40 => 'Desktop',
                        41 => 'mouse',
                        42 => 'Linux Desktop',
                    ),
                58 =>
                    array (
                        3 => 56,
                    ),
                59 =>
                    array (
                        3 => 0,
                        4 => 'NikiBot',
                        5 => 'NikiBot',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                60 =>
                    array (
                        3 => 59,
                    ),
                61 =>
                    array (
                        3 => 0,
                        4 => 'GrapeshotCrawler',
                        5 => 'GrapeshotCrawler',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                62 =>
                    array (
                        3 => 61,
                        10 => '2.0',
                        11 => '2',
                    ),
                63 =>
                    array (
                        3 => 61,
                    ),
                64 =>
                    array (
                        3 => 61,
                        5 => 'grapeFX',
                        10 => '0.9',
                        12 => '9',
                    ),
                65 =>
                    array (
                        3 => 61,
                        5 => 'grapeFX',
                    ),
                66 =>
                    array (
                        3 => 0,
                        4 => 'Anonymizied',
                        5 => 'Anonymizied',
                        6 => 'Bot/Crawler',
                        35 => 'true',
                    ),
                67 =>
                    array (
                        3 => 66,
                    ),
                68 =>
                    array (
                        3 => 66,
                    ),
                69 =>
                    array (
                        3 => 66,
                    ),
                70 =>
                    array (
                        3 => 66,
                    ),
                71 =>
                    array (
                        3 => 66,
                    ),
                72 =>
                    array (
                        3 => 66,
                    ),
                73 =>
                    array (
                        3 => 66,
                    ),
                74 =>
                    array (
                        3 => 66,
                    ),
                75 =>
                    array (
                        3 => 0,
                        4 => 'Yandex',
                        5 => 'Yandex',
                        6 => 'Bot/Crawler',
                        8 => 'Yandex',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                76 =>
                    array (
                        3 => 75,
                        5 => 'YandexAddURL',
                    ),
                77 =>
                    array (
                        3 => 75,
                        5 => 'YandexBlogs',
                    ),
                78 =>
                    array (
                        3 => 75,
                        5 => 'Yandex MirrorDetector',
                    ),
                79 =>
                    array (
                        3 => 75,
                        5 => 'YandexCatalog',
                    ),
                80 =>
                    array (
                        3 => 75,
                        5 => 'YandexDirect-Dyatel',
                    ),
                81 =>
                    array (
                        3 => 75,
                        5 => 'YandexFavicons',
                    ),
                82 =>
                    array (
                        3 => 75,
                        5 => 'YandexImageResizer',
                    ),
                83 =>
                    array (
                        3 => 75,
                        5 => 'YandexImages',
                    ),
                84 =>
                    array (
                        3 => 75,
                        5 => 'YandexMedia',
                    ),
                85 =>
                    array (
                        3 => 75,
                        5 => 'YandexMetrika',
                    ),
                86 =>
                    array (
                        3 => 75,
                        5 => 'YandexNews',
                    ),
                87 =>
                    array (
                        3 => 75,
                        5 => 'YandexVideo',
                    ),
                88 =>
                    array (
                        3 => 75,
                        5 => 'YandexWebmaster',
                    ),
                89 =>
                    array (
                        3 => 75,
                        5 => 'YandexZakladki',
                    ),
                90 =>
                    array (
                        3 => 75,
                    ),
                91 =>
                    array (
                        3 => 75,
                    ),
                92 =>
                    array (
                        3 => 75,
                    ),
                93 =>
                    array (
                        3 => 75,
                    ),
                94 =>
                    array (
                        3 => 75,
                    ),
                95 =>
                    array (
                        3 => 75,
                    ),
                96 =>
                    array (
                        3 => 75,
                    ),
                97 =>
                    array (
                        3 => 75,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.2',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                    ),
                98 =>
                    array (
                        3 => 75,
                        5 => 'YandexAddURL',
                    ),
                99 =>
                    array (
                        3 => 75,
                        5 => 'YandexCatalog',
                    ),
                100 =>
                    array (
                        3 => 75,
                        5 => 'YandexDirect-Dyatel',
                    ),
                101 =>
                    array (
                        3 => 75,
                        5 => 'YandexFavicons',
                    ),
                102 =>
                    array (
                        3 => 75,
                        5 => 'YandexImageResizer',
                    ),
                103 =>
                    array (
                        3 => 75,
                        5 => 'YandexImages',
                    ),
                104 =>
                    array (
                        3 => 75,
                        5 => 'YandexMedia',
                    ),
                105 =>
                    array (
                        3 => 75,
                        5 => 'YandexMetrika',
                    ),
                106 =>
                    array (
                        3 => 75,
                        5 => 'YandexNews',
                    ),
                107 =>
                    array (
                        3 => 75,
                        5 => 'YandexVideo',
                    ),
                108 =>
                    array (
                        3 => 0,
                        4 => 'Apache Bench',
                        5 => 'Apache Bench',
                        6 => 'Bot/Crawler',
                        8 => 'Apache Foundation',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                109 =>
                    array (
                        3 => 108,
                    ),
                110 =>
                    array (
                        3 => 0,
                        4 => 'YandexBot',
                        5 => 'YandexBot',
                        6 => 'Bot/Crawler',
                        8 => 'Yandex',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                111 =>
                    array (
                        3 => 110,
                        10 => '3.0',
                        11 => '3',
                    ),
                112 =>
                    array (
                        3 => 110,
                    ),
                113 =>
                    array (
                        3 => 0,
                        4 => 'Goldfire Server',
                        5 => 'Goldfire Server',
                        6 => 'Bot/Crawler',
                        8 => 'Invention Machine Corporation',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                114 =>
                    array (
                        3 => 113,
                    ),
                115 =>
                    array (
                        3 => 0,
                        4 => 'ArchitextSpider',
                        5 => 'ArchitextSpider',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                116 =>
                    array (
                        3 => 115,
                    ),
                117 =>
                    array (
                        3 => 0,
                        4 => 'Become',
                        5 => 'Become',
                        6 => 'Bot/Crawler',
                        34 => 'true',
                        35 => 'true',
                    ),
                118 =>
                    array (
                        3 => 117,
                        5 => 'BecomeBot',
                    ),
                119 =>
                    array (
                        3 => 117,
                        5 => 'BecomeBot',
                    ),
                120 =>
                    array (
                        3 => 117,
                        5 => 'MonkeyCrawl',
                    ),
                121 =>
                    array (
                        3 => 117,
                        5 => 'BecomeJPBot',
                    ),
                122 =>
                    array (
                        3 => 117,
                        5 => 'BecomeJPBot',
                    ),
                123 =>
                    array (
                        3 => 0,
                        4 => 'Convera',
                        5 => 'Convera',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                124 =>
                    array (
                        3 => 123,
                        5 => 'ConveraCrawler',
                    ),
                125 =>
                    array (
                        3 => 123,
                        5 => 'ConveraMultiMediaCrawler',
                        10 => '0.1',
                        12 => '1',
                    ),
                126 =>
                    array (
                        3 => 123,
                        5 => 'CrawlConvera',
                    ),
                127 =>
                    array (
                        3 => 123,
                        10 => '0.4',
                        12 => '4',
                    ),
                128 =>
                    array (
                        3 => 123,
                        10 => '0.5',
                        12 => '5',
                    ),
                129 =>
                    array (
                        3 => 123,
                        10 => '0.6',
                        12 => '6',
                    ),
                130 =>
                    array (
                        3 => 123,
                        10 => '0.7',
                        12 => '7',
                    ),
                131 =>
                    array (
                        3 => 123,
                        10 => '0.8',
                        12 => '8',
                    ),
                132 =>
                    array (
                        3 => 123,
                        10 => '0.9',
                        12 => '9',
                    ),
                133 =>
                    array (
                        3 => 0,
                        4 => 'Best of the Web',
                        5 => 'Best of the Web',
                        6 => 'Bot/Crawler',
                        8 => 'botw.org',
                        23 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                134 =>
                    array (
                        3 => 133,
                        5 => 'BOTW Feed Grabber',
                        34 => 'true',
                    ),
                135 =>
                    array (
                        3 => 133,
                        5 => 'BOTW Feed Grabber',
                        34 => 'true',
                    ),
                136 =>
                    array (
                        3 => 133,
                        5 => 'BOTW Spider',
                    ),
                137 =>
                    array (
                        3 => 133,
                        5 => 'BOTW Spider',
                    ),
                138 =>
                    array (
                        3 => 0,
                        4 => 'ContextAd Bot',
                        5 => 'ContextAd Bot',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                139 =>
                    array (
                        3 => 138,
                        10 => '1.0',
                        11 => '1',
                    ),
                140 =>
                    array (
                        3 => 138,
                    ),
                141 =>
                    array (
                        3 => 0,
                        4 => 'Java Standard Library',
                        5 => 'Java Standard Library',
                        6 => 'Bot/Crawler',
                        8 => 'Oracle',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                142 =>
                    array (
                        3 => 141,
                        10 => '1.4',
                        11 => '1',
                        12 => '4',
                    ),
                143 =>
                    array (
                        3 => 141,
                        10 => '1.5',
                        11 => '1',
                        12 => '5',
                    ),
                144 =>
                    array (
                        3 => 141,
                        10 => '1.6',
                        11 => '1',
                        12 => '6',
                    ),
                145 =>
                    array (
                        3 => 141,
                        10 => '1.7',
                        11 => '1',
                        12 => '7',
                    ),
                146 =>
                    array (
                        3 => 141,
                        10 => '1.17',
                        11 => '1',
                        12 => '17',
                    ),
                147 =>
                    array (
                        3 => 141,
                    ),
                148 =>
                    array (
                        3 => 0,
                        4 => 'DotBot',
                        5 => 'DotBot',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                149 =>
                    array (
                        3 => 148,
                    ),
                150 =>
                    array (
                        3 => 148,
                    ),
                151 =>
                    array (
                        3 => 148,
                        10 => '1.1',
                        11 => '1',
                        12 => '1',
                    ),
                152 =>
                    array (
                        3 => 148,
                    ),
                153 =>
                    array (
                        3 => 0,
                        4 => 'Bitlybot',
                        5 => 'BitlyBot',
                        6 => 'Bot/Crawler',
                        23 => 'true',
                        24 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                154 =>
                    array (
                        3 => 153,
                        10 => '2.0',
                        11 => '2',
                    ),
                155 =>
                    array (
                        3 => 153,
                    ),
                156 =>
                    array (
                        3 => 0,
                        4 => 'Entireweb',
                        5 => 'Entireweb',
                        6 => 'Bot/Crawler',
                        8 => 'Entireweb Sweden AB',
                        23 => 'true',
                        25 => 'true',
                        35 => 'true',
                    ),
                157 =>
                    array (
                        3 => 156,
                    ),
                158 =>
                    array (
                        3 => 156,
                        7 => '32',
                        13 => 'WinXP',
                        14 => '5.1',
                        15 => 'Windows XP',
                        16 => '32',
                        17 => 'Microsoft Corporation',
                        21 => 'true',
                    ),
                159 =>
                    array (
                        3 => 156,
                    ),
            ),
            $newBrowsers
        )
        ;
        self::assertEquals($browsers, $newBrowsers);

        $newUserAgents = $varUas->getValue($browscap);
        self::assertCount(22, $newUserAgents);
        self::assertSame(
            array (
                '0.0' => 'DefaultProperties',
                '1.0' => 'Ask',
                '4.0' => '360Spider',
                '49.0' => '80Legs',
                '51.0' => 'AhrefsBot',
                '56.0' => 'Adbeat',
                '59.0' => 'NikiBot',
                '61.0' => 'GrapeshotCrawler',
                '66.0' => 'Anonymizied',
                '75.0' => 'Yandex',
                '108.0' => 'Apache Bench',
                '110.0' => 'YandexBot',
                '113.0' => 'Goldfire Server',
                '115.0' => 'ArchitextSpider',
                '117.0' => 'Become',
                '123.0' => 'Convera',
                '133.0' => 'Best of the Web',
                '138.0' => 'ContextAd Bot',
                '141.0' => 'Java Standard Library',
                '148.0' => 'DotBot',
                '153.0' => 'bitlybot',
                '156.0' => 'Entireweb',
            ),
            $newUserAgents
        )
        ;
        self::assertEquals($userAgents, $newUserAgents);

        $newPatterns = $varPatt->getValue($browscap);
        self::assertCount(98, $newPatterns);
        self::assertSame(
            array (
                '@^Mozilla/.\\.0 \\(compatible; Ask Jeeves/Teoma.*\\)$@' => 2,
                '@^Mozilla/2\\.0 \\(compatible; Ask Jeeves\\)$@' => 3,
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT 5\\.0; .*WOW64.*Trident/4\\.0.*\\).* 360Spider$@' => 5,
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT 5\\.01.*Trident/4\\.0.*\\).* 360Spider$@' => 6,
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT (\\d)\\.(\\d).*Trident/4\\.0.*\\).* 360Spider$@' =>
                    array (
                        '@5|0' => 7,
                        '@5|1' => 10,
                        '@5|2' => 13,
                        '@6|0' => 17,
                        '@6|1' => 20,
                        '@6|2' => 23,
                    ),
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT (\\d)\\.(\\d);.*Win64. x64.*Trident/4\\.0.*\\).* 360Spider$@' =>
                    array (
                        '@5|1' => 8,
                        '@5|2' => 11,
                        '@6|0' => 15,
                    ),
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT (\\d)\\.(\\d).*WOW64.*Trident/4\\.0.*\\).* 360Spider$@' =>
                    array (
                        '@5|1' => 9,
                        '@5|2' => 12,
                        '@6|0' => 16,
                        '@6|1' => 19,
                        '@6|2' => 22,
                    ),
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT 5\\.2;.*Win64.*Trident/4\\.0.*\\).* 360Spider$@' => 14,
                '@^Mozilla/4\\.0 \\(compatible; MSIE 8\\.0.*; .*Windows NT 6\\.(\\d).*Win64. x64.*Trident/4\\.0.*\\).* 360Spider$@' =>
                    array (
                        '@1' => 18,
                        '@2' => 21,
                    ),
                '@^Mozilla/5\\.0 \\(compatible; MSIE 9\\.0.*; .*Windows NT (\\d)\\.(\\d).*WOW64.*Trident/5\\.0.* 360Spider$@' =>
                    array (
                        '@5|1' => 24,
                        '@5|2' => 26,
                        '@6|0' => 28,
                        '@6|1' => 31,
                        '@6|2' => 34,
                    ),
                '@^Mozilla/5\\.0 \\(compatible; MSIE 9\\.0.*; .*Windows NT (\\d)\\.(\\d).*Trident/5\\.0.* 360Spider$@' =>
                    array (
                        '@5|1' => 25,
                        '@5|2' => 27,
                        '@6|0' => 29,
                        '@6|1' => 32,
                        '@6|2' => 35,
                    ),
                '@^Mozilla/5\\.0 \\(compatible; MSIE 9\\.0.*; .*Windows NT 6\\.(\\d).*Win64. x64.*Trident/5\\.0.* 360Spider$@' =>
                    array (
                        '@1' => 30,
                        '@2' => 33,
                    ),
                '@^Mozilla/5\\.0 \\(.*Windows NT 5\\.1.*\\)  Firefox/.*; 360Spider.*$@' => 36,
                '@^Mozilla/5\\.0 \\(.*Windows NT (\\d)\\.(\\d).*WOW64.*\\) AppleWebKit/.* \\(KHTML, like Gecko\\) Chrome/.* Safari/.*; 360Spider.*$@' =>
                    array (
                        '@5|1' => 37,
                        '@5|2' => 39,
                        '@6|0' => 41,
                        '@6|1' => 44,
                        '@6|2' => 47,
                    ),
                '@^Mozilla/5\\.0 \\(.*Windows NT (\\d)\\.(\\d).*\\) AppleWebKit/.* \\(KHTML, like Gecko\\) Chrome/.* Safari/.*; 360Spider.*$@' =>
                    array (
                        '@5|1' => 38,
                        '@5|2' => 40,
                        '@6|0' => 42,
                        '@6|1' => 45,
                        '@6|2' => 48,
                    ),
                '@^Mozilla/5\\.0 \\(.*Windows NT 6\\.(\\d).*Win64. x64.*\\) AppleWebKit/.* \\(KHTML, like Gecko\\) Chrome/.* Safari/.*; 360Spider.*$@' =>
                    array (
                        '@1' => 43,
                        '@2' => 46,
                    ),
                '@^Mozilla/5\\.0 \\(compatible; .*; http\\://www\\.80legs\\.com/.*\\) Gecko/.*$@' => 50,
                '@^Mozilla/5\\.0 \\(compatible; AhrefsBot/(\\d)\\.(\\d).*$@' =>
                    array (
                        '@3|1' => 52,
                        '@4|0' => 53,
                        '@5|0' => 54,
                    ),
                '@^Mozilla/5\\.0 \\(compatible; AhrefsBot/.*$@' => 55,
                '@^Mozilla/5\\.0 \\(.*Linux x86.*\\) adbeat\\.com.* Gecko/.* Firefox/.*AppleWebKit/.*Safari/.*$@' => 57,
                '@^adbeat_bot.*$@' => 58,
                '@^niki\\-bot$@' => 60,
                '@^Mozilla/5\\.0 \\(compatible; GrapeshotCrawler/2\\.0; \\+http\\://www\\.grapeshot\\.co\\.uk/crawler\\.php\\)$@' => 62,
                '@^Mozilla/5\\.0 \\(compatible; GrapeshotCrawler/.*; \\+http\\://www\\.grapeshot\\.co\\.uk/crawler\\.php\\)$@' => 63,
                '@^Mozilla/5\\.0 \\(compatible; grapeFX/0\\.9; crawler\\@grapeshot\\.co\\.uk$@' => 64,
                '@^Mozilla/5\\.0 \\(compatible; grapeFX/.*; crawler\\@grapeshot\\.co\\.uk$@' => 65,
                '@^Anonymisiert durch AlMiSoft Browser\\-Maulkorb \\(Anonymisier.*$@' => 67,
                '@^Anonymisiert.*$@' => 68,
                '@^Anonymizer/.*$@' => 69,
                '@^Anonymizied.*$@' => 70,
                '@^Anonymous.*$@' => 71,
                '@^Anonymous/.*$@' => 72,
                '@^http\\://Anonymouse\\.org/.*$@' => 73,
                '@^Mozilla/5\\.0 \\(Randomized by FreeSafeIP.*$@' => 74,
                '@^Mozilla/5\\.0 \\(compatible; YandexAddurl/.*\\)$@' => 76,
                '@^Mozilla/5\\.0 \\(compatible; YandexBlogs/.*\\)$@' => 77,
                '@^Mozilla/5\\.0 \\(compatible; YandexBot/.*; MirrorDetector.*\\)$@' => 78,
                '@^Mozilla/5\\.0 \\(compatible; YandexCatalog/.*\\)$@' => 79,
                '@^Mozilla/5\\.0 \\(compatible; YandexDirect/.*\\)$@' => 80,
                '@^Mozilla/5\\.0 \\(compatible; YandexFavicons/.*\\)$@' => 81,
                '@^Mozilla/5\\.0 \\(compatible; YandexImageResizer/.*\\)$@' => 82,
                '@^Mozilla/5\\.0 \\(compatible; YandexImages/.*\\)$@' => 83,
                '@^Mozilla/5\\.0 \\(compatible; YandexMedia/.*\\)$@' => 84,
                '@^Mozilla/5\\.0 \\(compatible; YandexMetrika/.*\\)$@' => 85,
                '@^Mozilla/5\\.0 \\(compatible; YandexNews/.*\\)$@' => 86,
                '@^Mozilla/5\\.0 \\(compatible; YandexVideo/.*\\)$@' => 87,
                '@^Mozilla/5\\.0 \\(compatible; YandexWebmaster/.*\\)$@' => 88,
                '@^Mozilla/5\\.0 \\(compatible; YandexZakladki/.*\\)$@' => 89,
                '@^Yandex/1\\.01\\.001 \\(compatible; Win16; .*\\)$@' => 90,
                '@^Mozilla/4\\.0 \\(.*compatible.*;.*MSIE 5\\.0; YANDEX\\)$@' => 91,
                '@^Mozilla/5\\.0 \\(compatible; YandexBot/.*; MirrorDetector\\)$@' => 92,
                '@^Mozilla/5\\.0 \\(compatible; YandexZakladki/.*; Dyatel; \\+http\\://yandex\\.com/bots\\)$@' => 93,
                '@^YaDirectBot/.*$@' => 94,
                '@^Yandex/.*$@' => 95,
                '@^YandexSomething/.*$@' => 96,
                '@^Mozilla/5\\.0 \\(Windows; .; Windows NT 5\\.2; en\\-US; rv\\:1\\.9\\) Gecko VisualParser/3\\.0$@' => 97,
                '@^Mozilla/5\\.0 \\(compatible; YandexAddurl/.*$@' => 98,
                '@^Mozilla/5\\.0 \\(compatible; YandexCatalog/.*$@' => 99,
                '@^Mozilla/5\\.0 \\(compatible; YandexDirect/.*$@' => 100,
                '@^Mozilla/5\\.0 \\(compatible; YandexFavicons/.*$@' => 101,
                '@^Mozilla/5\\.0 \\(compatible; YandexImageResizer/.*$@' => 102,
                '@^Mozilla/5\\.0 \\(compatible; YandexImages/.*$@' => 103,
                '@^Mozilla/5\\.0 \\(compatible; YandexMedia/.*$@' => 104,
                '@^Mozilla/5\\.0 \\(compatible; YandexMetrika/.*$@' => 105,
                '@^Mozilla/5\\.0 \\(compatible; YandexNews/.*$@' => 106,
                '@^Mozilla/5\\.0 \\(compatible; YandexVideo/.*$@' => 107,
                '@^ApacheBench/.*$@' => 109,
                '@^Mozilla/5\\.0 \\(compatible; YandexBot/3\\.0.*$@' => 111,
                '@^Mozilla/5\\.0 \\(compatible; YandexBot/.*$@' => 112,
                '@^Goldfire Server.*$@' => 114,
                '@^ArchitextSpider.*$@' => 116,
                '@^.*BecomeBot/.*$@' => 118,
                '@^.*BecomeBot\\@exava\\.com.*$@' => 119,
                '@^MonkeyCrawl/.*$@' => 120,
                '@^Mozilla/5\\.0 \\(compatible; BecomeJPBot/2\\.3; .*\\)$@' => 121,
                '@^Mozilla/5\\.0 \\(compatible; BecomeJPBot/2\\.3.*\\)$@' => 122,
                '@^ConveraCrawler/.*$@' => 124,
                '@^ConveraMultiMediaCrawler/0\\.1.*$@' => 125,
                '@^CrawlConvera.*$@' => 126,
                '@^ConveraCrawler/0\\.(\\d).*$@' =>
                    array (
                        '@4' => 127,
                        '@5' => 128,
                        '@6' => 129,
                        '@7' => 130,
                        '@8' => 131,
                        '@9' => 132,
                    ),
                '@^Mozilla/4\\.0 \\(compatible; BOTW Feed Grabber.*http\\://botw\\.org\\)$@' => 134,
                '@^Mozilla/4\\.0 \\(compatible; BOTW Feed Grabber; .*http\\://botw\\.org\\)$@' => 135,
                '@^Mozilla/4\\.0 \\(compatible; BOTW Spider; .*http\\://botw\\.org\\)$@' => 136,
                '@^Mozilla/4\\.0 \\(compatible; BOTW Spider.*http\\://botw\\.org\\)$@' => 137,
                '@^ContextAd Bot 1\\.0.*$@' => 139,
                '@^ContextAd Bot.*$@' => 140,
                '@^Java/1\\.(\\d).*$@' =>
                    array (
                        '@4' => 142,
                        '@5' => 143,
                        '@6' => 144,
                        '@7' => 145,
                    ),
                '@^Java/1\\.17.*$@' => 146,
                '@^Java/.*$@' => 147,
                '@^DotBot/.* \\(http\\://www\\.dotnetdotcom\\.org/.*\\)$@' => 149,
                '@^Mozilla/5\\.0 \\(compatible; DotBot/.*; http\\://www\\.dotnetdotcom\\.org/.*\\)$@' => 150,
                '@^Mozilla/5\\.0 \\(compatible; DotBot/1\\.1; http\\://www\\.opensiteexplorer\\.org/dotbot.*\\)$@' => 151,
                '@^Mozilla/5\\.0 \\(compatible; DotBot/.*; http\\://www\\.opensiteexplorer\\.org/dotbot.*\\)$@' => 152,
                '@^bitlybot/2\\..*$@' => 154,
                '@^bitlybot.*$@' => 155,
                '@^Mozilla/5\\.0 \\(compatible; Speedy Spider; .*$@' => 157,
                '@^Mozilla/5\\.0 \\(Windows; .; Windows NT 5\\.1; .*\\) Speedy Spider .*$@' => 158,
                '@^Speedy Spider .*$@' => 159,
            ),
            $newPatterns
        );
        self::assertEquals($patterns, $newPatterns);
    }

    public function dataCtreateCache()
    {
        return array(
            array(
                ';;; Provided courtesy of http://browscap.org/
;;; Created on Thursday, June 18, 2015 at 11:21 PM CEST
;;; Keep up with the latest goings-on with the project:
;;; Follow us on Twitter <https://twitter.com/browscap>, or...
;;; Like us on Facebook <https://facebook.com/browscap>, or...
;;; Collaborate on GitHub <https://github.com/browscap>, or...
;;; Discuss on Google Groups <https://groups.google.com/forum/#!forum/browscap>.

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=test
Released=Thu, 18 Jun 2015 23:21:38 +0200
Format=php
Type=FULL

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; DefaultProperties

[DefaultProperties]
Comment="DefaultProperties"
Browser="DefaultProperties"
Browser_Type="unknown"
Browser_Bits="0"
Browser_Maker="unknown"
Browser_Modus="unknown"
Version="0.0"
MajorVer=0
MinorVer=0
Platform="unknown"
Platform_Version=unknown
Platform_Description="unknown"
Platform_Bits="0"
Platform_Maker="unknown"
Alpha="false"
Beta="false"
Win16="false"
Win32="false"
Win64="false"
Frames="false"
IFrames="false"
Tables="false"
Cookies="false"
BackgroundSounds="false"
JavaScript="false"
VBScript="false"
JavaApplets="false"
ActiveXControls="false"
isMobileDevice="false"
isTablet="false"
isSyndicationReader="false"
Crawler="false"
CssVersion=0
AolVersion=0
Device_Name="unknown"
Device_Maker="unknown"
Device_Type="unknown"
Device_Pointing_Method="unknown"
Device_Code_Name="unknown"
Device_Brand_Name="unknown"
RenderingEngine_Name="unknown"
RenderingEngine_Version=unknown
RenderingEngine_Description="unknown"
RenderingEngine_Maker="unknown"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Ask

[Ask]
Parent="DefaultProperties"
Comment="Ask"
Browser="Ask"
Browser_Type="Bot/Crawler"
Browser_Maker="Ask.com"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)]
Parent="Ask"
Browser="Teoma"

[Mozilla/2.0 (compatible; Ask Jeeves)]
Parent="Ask"
Browser="AskJeeves"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; 360Spider

[360Spider]
Parent="DefaultProperties"
Comment="360Spider"
Browser="360Spider"
Browser_Type="Bot/Crawler"
Browser_Maker="so.360.cn"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 5.0; *WOW64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win2000"
Platform_Version="5.0"
Platform_Description="Windows 2000"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 5.01*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win2000"
Platform_Version="5.01"
Platform_Description="Windows 2000"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 5.0*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win2000"
Platform_Version="5.0"
Platform_Description="Windows 2000"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 5.1;*Win64? x64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="64"
Platform="WinXP"
Platform_Version="5.1"
Platform_Description="Windows XP"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 5.1*WOW64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.1"
Platform_Description="Windows XP"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 5.1*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.1"
Platform_Description="Windows XP"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 5.2;*Win64? x64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="64"
Platform="WinXP"
Platform_Version="5.2"
Platform_Description="Windows XP"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 5.2*WOW64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.2"
Platform_Description="Windows XP"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 5.2*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.2"
Platform_Description="Windows XP"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 5.2;*Win64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.2"
Platform_Description="Windows XP"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 6.0;*Win64? x64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="64"
Platform="WinVista"
Platform_Version="6.0"
Platform_Description="Windows Vista"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 6.0*WOW64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinVista"
Platform_Version="6.0"
Platform_Description="Windows Vista"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 6.0*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinVista"
Platform_Version="6.0"
Platform_Description="Windows Vista"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 6.1*Win64? x64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="64"
Platform="Win7"
Platform_Version="6.1"
Platform_Description="Windows 7"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 6.1*WOW64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win7"
Platform_Version="6.1"
Platform_Description="Windows 7"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 6.1*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win7"
Platform_Version="6.1"
Platform_Description="Windows 7"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 6.2*Win64? x64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="64"
Platform="Win8"
Platform_Version="6.2"
Platform_Description="Windows 8"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 6.2*WOW64*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win8"
Platform_Version="6.2"
Platform_Description="Windows 8"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/4.0 (compatible; MSIE 8.0*; *Windows NT 6.2*Trident/4.0*)* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win8"
Platform_Version="6.2"
Platform_Description="Windows 8"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 5.1*WOW64*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.1"
Platform_Description="Windows XP"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 5.1*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.1"
Platform_Description="Windows XP"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 5.2*WOW64*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.2"
Platform_Description="Windows XP"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 5.2*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.2"
Platform_Description="Windows XP"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 6.0*WOW64*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinVista"
Platform_Version="6.0"
Platform_Description="Windows Vista"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 6.0*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="WinVista"
Platform_Version="6.0"
Platform_Description="Windows Vista"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 6.1*Win64? x64*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="64"
Platform="Win7"
Platform_Version="6.1"
Platform_Description="Windows 7"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 6.1*WOW64*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win7"
Platform_Version="6.1"
Platform_Description="Windows 7"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 6.1*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win7"
Platform_Version="6.1"
Platform_Description="Windows 7"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 6.2*Win64? x64*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="64"
Platform="Win8"
Platform_Version="6.2"
Platform_Description="Windows 8"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 6.2*WOW64*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win8"
Platform_Version="6.2"
Platform_Description="Windows 8"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (compatible; MSIE 9.0*; *Windows NT 6.2*Trident/5.0* 360Spider]
Parent="360Spider"
Browser_Bits="32"
Platform="Win8"
Platform_Version="6.2"
Platform_Description="Windows 8"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (*Windows NT 5.1*)  Firefox/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.1"
Platform_Description="Windows XP"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"

[Mozilla/5.0 (*Windows NT 5.1*WOW64*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.1"
Platform_Description="Windows XP"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 5.1*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.1"
Platform_Description="Windows XP"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 5.2*WOW64*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.2"
Platform_Description="Windows XP"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 5.2*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.2"
Platform_Description="Windows XP"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 6.0*WOW64*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="WinVista"
Platform_Version="6.0"
Platform_Description="Windows Vista"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 6.0*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="WinVista"
Platform_Version="6.0"
Platform_Description="Windows Vista"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 6.1*Win64? x64*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="64"
Platform="Win7"
Platform_Version="6.1"
Platform_Description="Windows 7"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 6.1*WOW64*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="Win7"
Platform_Version="6.1"
Platform_Description="Windows 7"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 6.1*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="Win7"
Platform_Version="6.1"
Platform_Description="Windows 7"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 6.2*Win64? x64*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="64"
Platform="Win8"
Platform_Version="6.2"
Platform_Description="Windows 8"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 6.2*WOW64*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="Win8"
Platform_Version="6.2"
Platform_Description="Windows 8"
Platform_Bits="64"
Platform_Maker="Microsoft Corporation"
Win64="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

[Mozilla/5.0 (*Windows NT 6.2*) AppleWebKit/* (KHTML, like Gecko) Chrome/* Safari/*; 360Spider*]
Parent="360Spider"
Browser_Bits="32"
Platform="Win8"
Platform_Version="6.2"
Platform_Description="Windows 8"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"
JavaApplets="true"
Device_Name="Windows Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Windows Desktop"
RenderingEngine_Name="WebKit"
RenderingEngine_Description="For Google Chrome, iOS (including both mobile Safari, WebViews within third-party apps, and web clips), Safari, Arora, Midori, OmniWeb, Shiira, iCab since version 4, Web, SRWare Iron, Rekonq, and in Maxthon 3."
RenderingEngine_Maker="Apple Inc"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; 80Legs

[80Legs]
Parent="DefaultProperties"
Comment="80Legs"
Browser="80Legs"
Browser_Type="Bot/Crawler"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[Mozilla/5.0 (compatible; *; http://www.80legs.com/*) Gecko/*]
Parent="80Legs"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; AhrefsBot

[AhrefsBot]
Parent="DefaultProperties"
Comment="AhrefsBot"
Browser="AhrefsBot"
Browser_Type="Bot/Crawler"
Browser_Maker="Ahrefs Pte Ltd"
Crawler="true"

[Mozilla/5.0 (compatible; AhrefsBot/3.1*]
Parent="AhrefsBot"
Version="3.1"
MajorVer=3
MinorVer=1

[Mozilla/5.0 (compatible; AhrefsBot/4.0*]
Parent="AhrefsBot"
Version="4.0"
MajorVer=4

[Mozilla/5.0 (compatible; AhrefsBot/5.0*]
Parent="AhrefsBot"
Version="5.0"
MajorVer=5

[Mozilla/5.0 (compatible; AhrefsBot/*]
Parent="AhrefsBot"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Adbeat

[Adbeat]
Parent="DefaultProperties"
Comment="Adbeat"
Browser="Adbeat Bot"
Browser_Type="Bot/Crawler"
Browser_Maker="adbeat.com"
Crawler="true"

[Mozilla/5.0 (*Linux x86*) adbeat.com* Gecko/* Firefox/*AppleWebKit/*Safari/*]
Parent="Adbeat"
Browser_Bits="32"
Platform="Linux"
Platform_Description="Linux"
Platform_Bits="32"
Platform_Maker="Linux Foundation"
Device_Name="Linux Desktop"
Device_Maker="Various"
Device_Type="Desktop"
Device_Pointing_Method="mouse"
Device_Code_Name="Linux Desktop"

[adbeat_bot*]
Parent="Adbeat"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; NikiBot

[NikiBot]
Parent="DefaultProperties"
Comment="NikiBot"
Browser="NikiBot"
Browser_Type="Bot/Crawler"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[niki-bot]
Parent="NikiBot"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; GrapeshotCrawler

[GrapeshotCrawler]
Parent="DefaultProperties"
Comment="GrapeshotCrawler"
Browser="GrapeshotCrawler"
Browser_Type="Bot/Crawler"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[Mozilla/5.0 (compatible; GrapeshotCrawler/2.0; +http://www.grapeshot.co.uk/crawler.php)]
Parent="GrapeshotCrawler"
Version="2.0"
MajorVer=2

[Mozilla/5.0 (compatible; GrapeshotCrawler/*; +http://www.grapeshot.co.uk/crawler.php)]
Parent="GrapeshotCrawler"

[Mozilla/5.0 (compatible; grapeFX/0.9; crawler@grapeshot.co.uk]
Parent="GrapeshotCrawler"
Browser="grapeFX"
Version="0.9"
MinorVer=9

[Mozilla/5.0 (compatible; grapeFX/*; crawler@grapeshot.co.uk]
Parent="GrapeshotCrawler"
Browser="grapeFX"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Anonymizied

[Anonymizied]
Parent="DefaultProperties"
Comment="Anonymizied"
Browser="Anonymizied"
Browser_Type="Bot/Crawler"
Crawler="true"

[Anonymisiert durch AlMiSoft Browser-Maulkorb (Anonymisier*]
Parent="Anonymizied"

[Anonymisiert*]
Parent="Anonymizied"

[Anonymizer/*]
Parent="Anonymizied"

[Anonymizied*]
Parent="Anonymizied"

[Anonymous*]
Parent="Anonymizied"

[Anonymous/*]
Parent="Anonymizied"

[http://Anonymouse.org/*]
Parent="Anonymizied"

[Mozilla/5.0 (Randomized by FreeSafeIP*]
Parent="Anonymizied"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Yandex

[Yandex]
Parent="DefaultProperties"
Comment="Yandex"
Browser="Yandex"
Browser_Type="Bot/Crawler"
Browser_Maker="Yandex"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[Mozilla/5.0 (compatible; YandexAddurl/*)]
Parent="Yandex"
Browser="YandexAddURL"

[Mozilla/5.0 (compatible; YandexBlogs/*)]
Parent="Yandex"
Browser="YandexBlogs"

[Mozilla/5.0 (compatible; YandexBot/*; MirrorDetector*)]
Parent="Yandex"
Browser="Yandex MirrorDetector"

[Mozilla/5.0 (compatible; YandexCatalog/*)]
Parent="Yandex"
Browser="YandexCatalog"

[Mozilla/5.0 (compatible; YandexDirect/*)]
Parent="Yandex"
Browser="YandexDirect-Dyatel"

[Mozilla/5.0 (compatible; YandexFavicons/*)]
Parent="Yandex"
Browser="YandexFavicons"

[Mozilla/5.0 (compatible; YandexImageResizer/*)]
Parent="Yandex"
Browser="YandexImageResizer"

[Mozilla/5.0 (compatible; YandexImages/*)]
Parent="Yandex"
Browser="YandexImages"

[Mozilla/5.0 (compatible; YandexMedia/*)]
Parent="Yandex"
Browser="YandexMedia"

[Mozilla/5.0 (compatible; YandexMetrika/*)]
Parent="Yandex"
Browser="YandexMetrika"

[Mozilla/5.0 (compatible; YandexNews/*)]
Parent="Yandex"
Browser="YandexNews"

[Mozilla/5.0 (compatible; YandexVideo/*)]
Parent="Yandex"
Browser="YandexVideo"

[Mozilla/5.0 (compatible; YandexWebmaster/*)]
Parent="Yandex"
Browser="YandexWebmaster"

[Mozilla/5.0 (compatible; YandexZakladki/*)]
Parent="Yandex"
Browser="YandexZakladki"

[Yandex/1.01.001 (compatible; Win16; *)]
Parent="Yandex"

[Mozilla/4.0 (*compatible*;*MSIE 5.0; YANDEX)]
Parent="Yandex"

[Mozilla/5.0 (compatible; YandexBot/*; MirrorDetector)]
Parent="Yandex"

[Mozilla/5.0 (compatible; YandexZakladki/*; Dyatel; +http://yandex.com/bots)]
Parent="Yandex"

[YaDirectBot/*]
Parent="Yandex"

[Yandex/*]
Parent="Yandex"

[YandexSomething/*]
Parent="Yandex"

[Mozilla/5.0 (Windows; ?; Windows NT 5.2; en-US; rv:1.9) Gecko VisualParser/3.0]
Parent="Yandex"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.2"
Platform_Description="Windows XP"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"

[Mozilla/5.0 (compatible; YandexAddurl/*]
Parent="Yandex"
Browser="YandexAddURL"

[Mozilla/5.0 (compatible; YandexCatalog/*]
Parent="Yandex"
Browser="YandexCatalog"

[Mozilla/5.0 (compatible; YandexDirect/*]
Parent="Yandex"
Browser="YandexDirect-Dyatel"

[Mozilla/5.0 (compatible; YandexFavicons/*]
Parent="Yandex"
Browser="YandexFavicons"

[Mozilla/5.0 (compatible; YandexImageResizer/*]
Parent="Yandex"
Browser="YandexImageResizer"

[Mozilla/5.0 (compatible; YandexImages/*]
Parent="Yandex"
Browser="YandexImages"

[Mozilla/5.0 (compatible; YandexMedia/*]
Parent="Yandex"
Browser="YandexMedia"

[Mozilla/5.0 (compatible; YandexMetrika/*]
Parent="Yandex"
Browser="YandexMetrika"

[Mozilla/5.0 (compatible; YandexNews/*]
Parent="Yandex"
Browser="YandexNews"

[Mozilla/5.0 (compatible; YandexVideo/*]
Parent="Yandex"
Browser="YandexVideo"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Apache Bench

[Apache Bench]
Parent="DefaultProperties"
Comment="Apache Bench"
Browser="Apache Bench"
Browser_Type="Bot/Crawler"
Browser_Maker="Apache Foundation"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[ApacheBench/*]
Parent="Apache Bench"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; YandexBot

[YandexBot]
Parent="DefaultProperties"
Comment="YandexBot"
Browser="YandexBot"
Browser_Type="Bot/Crawler"
Browser_Maker="Yandex"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[Mozilla/5.0 (compatible; YandexBot/3.0*]
Parent="YandexBot"
Version="3.0"
MajorVer=3

[Mozilla/5.0 (compatible; YandexBot/*]
Parent="YandexBot"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Goldfire Server

[Goldfire Server]
Parent="DefaultProperties"
Comment="Goldfire Server"
Browser="Goldfire Server"
Browser_Type="Bot/Crawler"
Browser_Maker="Invention Machine Corporation"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[Goldfire Server*]
Parent="Goldfire Server"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; ArchitextSpider

[ArchitextSpider]
Parent="DefaultProperties"
Comment="ArchitextSpider"
Browser="ArchitextSpider"
Browser_Type="Bot/Crawler"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[ArchitextSpider*]
Parent="ArchitextSpider"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Become

[Become]
Parent="DefaultProperties"
Comment="Become"
Browser="Become"
Browser_Type="Bot/Crawler"
isSyndicationReader="true"
Crawler="true"

[*BecomeBot/*]
Parent="Become"
Browser="BecomeBot"

[*BecomeBot@exava.com*]
Parent="Become"
Browser="BecomeBot"

[MonkeyCrawl/*]
Parent="Become"
Browser="MonkeyCrawl"

[Mozilla/5.0 (compatible; BecomeJPBot/2.3; *)]
Parent="Become"
Browser="BecomeJPBot"

[Mozilla/5.0 (compatible; BecomeJPBot/2.3*)]
Parent="Become"
Browser="BecomeJPBot"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Convera

[Convera]
Parent="DefaultProperties"
Comment="Convera"
Browser="Convera"
Browser_Type="Bot/Crawler"
Frames="true"
Tables="true"
Crawler="true"

[ConveraCrawler/*]
Parent="Convera"
Browser="ConveraCrawler"

[ConveraMultiMediaCrawler/0.1*]
Parent="Convera"
Browser="ConveraMultiMediaCrawler"
Version="0.1"
MinorVer=1

[CrawlConvera*]
Parent="Convera"
Browser="CrawlConvera"

[ConveraCrawler/0.4*]
Parent="Convera"
Version="0.4"
MinorVer=4

[ConveraCrawler/0.5*]
Parent="Convera"
Version="0.5"
MinorVer=5

[ConveraCrawler/0.6*]
Parent="Convera"
Version="0.6"
MinorVer=6

[ConveraCrawler/0.7*]
Parent="Convera"
Version="0.7"
MinorVer=7

[ConveraCrawler/0.8*]
Parent="Convera"
Version="0.8"
MinorVer=8

[ConveraCrawler/0.9*]
Parent="Convera"
Version="0.9"
MinorVer=9

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Best of the Web

[Best of the Web]
Parent="DefaultProperties"
Comment="Best of the Web"
Browser="Best of the Web"
Browser_Type="Bot/Crawler"
Browser_Maker="botw.org"
Frames="true"
Tables="true"
Crawler="true"

[Mozilla/4.0 (compatible; BOTW Feed Grabber*http://botw.org)]
Parent="Best of the Web"
Browser="BOTW Feed Grabber"
isSyndicationReader="true"

[Mozilla/4.0 (compatible; BOTW Feed Grabber; *http://botw.org)]
Parent="Best of the Web"
Browser="BOTW Feed Grabber"
isSyndicationReader="true"

[Mozilla/4.0 (compatible; BOTW Spider; *http://botw.org)]
Parent="Best of the Web"
Browser="BOTW Spider"

[Mozilla/4.0 (compatible; BOTW Spider*http://botw.org)]
Parent="Best of the Web"
Browser="BOTW Spider"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; ContextAd Bot

[ContextAd Bot]
Parent="DefaultProperties"
Comment="ContextAd Bot"
Browser="ContextAd Bot"
Browser_Type="Bot/Crawler"
Frames="true"
Tables="true"
Crawler="true"

[ContextAd Bot 1.0*]
Parent="ContextAd Bot"
Version="1.0"
MajorVer=1

[ContextAd Bot*]
Parent="ContextAd Bot"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Java Standard Library

[Java Standard Library]
Parent="DefaultProperties"
Comment="Java Standard Library"
Browser="Java Standard Library"
Browser_Type="Bot/Crawler"
Browser_Maker="Oracle"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[Java/1.4*]
Parent="Java Standard Library"
Version="1.4"
MajorVer=1
MinorVer=4

[Java/1.5*]
Parent="Java Standard Library"
Version="1.5"
MajorVer=1
MinorVer=5

[Java/1.6*]
Parent="Java Standard Library"
Version="1.6"
MajorVer=1
MinorVer=6

[Java/1.7*]
Parent="Java Standard Library"
Version="1.7"
MajorVer=1
MinorVer=7

[Java/1.17*]
Parent="Java Standard Library"
Version="1.17"
MajorVer=1
MinorVer=17

[Java/*]
Parent="Java Standard Library"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; DotBot

[DotBot]
Parent="DefaultProperties"
Comment="DotBot"
Browser="DotBot"
Browser_Type="Bot/Crawler"
Frames="true"
Tables="true"
Crawler="true"

[DotBot/* (http://www.dotnetdotcom.org/*)]
Parent="DotBot"

[Mozilla/5.0 (compatible; DotBot/*; http://www.dotnetdotcom.org/*)]
Parent="DotBot"

[Mozilla/5.0 (compatible; DotBot/1.1; http://www.opensiteexplorer.org/dotbot*)]
Parent="DotBot"
Version="1.1"
MajorVer=1
MinorVer=1

[Mozilla/5.0 (compatible; DotBot/*; http://www.opensiteexplorer.org/dotbot*)]
Parent="DotBot"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; bitlybot

[bitlybot]
Parent="DefaultProperties"
Comment="Bitlybot"
Browser="BitlyBot"
Browser_Type="Bot/Crawler"
Frames="true"
IFrames="true"
Tables="true"
Crawler="true"

[bitlybot/2.*]
Parent="bitlybot"
Version="2.0"
MajorVer=2

[bitlybot*]
Parent="bitlybot"

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Entireweb

[Entireweb]
Parent="DefaultProperties"
Comment="Entireweb"
Browser="Entireweb"
Browser_Type="Bot/Crawler"
Browser_Maker="Entireweb Sweden AB"
Frames="true"
Tables="true"
Crawler="true"

[Mozilla/5.0 (compatible; Speedy Spider; *]
Parent="Entireweb"

[Mozilla/5.0 (Windows; ?; Windows NT 5.1; *) Speedy Spider *]
Parent="Entireweb"
Browser_Bits="32"
Platform="WinXP"
Platform_Version="5.1"
Platform_Description="Windows XP"
Platform_Bits="32"
Platform_Maker="Microsoft Corporation"
Win32="true"

[Speedy Spider *]
Parent="Entireweb"'
            )
        );
    }
}
