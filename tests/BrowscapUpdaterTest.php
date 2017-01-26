<?php

namespace BrowscapPHPTest;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Helper\Exception;
use BrowscapPHP\Helper\IniLoader;
use org\bovigo\vfs\vfsStream;
use WurflCache\Adapter\Memory;

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
class BrowscapUpdaterTest extends \PHPUnit_Framework_TestCase
{
    const STORAGE_DIR = 'storage';

    /**
     * @var \BrowscapPHP\BrowscapUpdater
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $this->object = new BrowscapUpdater();
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
    public function testGetLogger()
    {
        self::assertInstanceOf('\Psr\Log\NullLogger', $this->object->getLogger());
    }

    /**
     *
     */
    public function testSetConnectTimeout()
    {
        $timeout = 25;

        $reflection = new \ReflectionObject($this->object);
        $property   = $reflection->getProperty('connectTimeout');
        $property->setAccessible(true);

        $this->object->setConnectTimeout($timeout);

        self::assertSame($timeout, $property->getValue($this->object));
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
     *
     */
    public function testGetClient()
    {
        self::assertInstanceOf('\GuzzleHttp\Client', $this->object->getClient());
    }

    /**
     *
     */
    public function testSetGetClient()
    {
        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object->setClient($client);
        self::assertSame($client, $this->object->getClient());
    }

    /**
     * tests if an exception is thrown if no file name was given
     *
     * @expectedException \BrowscapPHP\Exception
     * @expectedExceptionMessage the file name can not be empty
     */
    public function testConvertEmptyFile()
    {
        $this->object->convertFile(null);
    }

    /**
     * tests if an exception is thrown if no file name was given
     *
     * @expectedException \BrowscapPHP\Exception
     * @expectedExceptionMessage it was not possible to read the local file /this/file/does/not/exist
     */
    public function testConvertNotReadableFile()
    {
        $this->object->convertFile('/this/file/does/not/exist');
    }

    /**
     *
     */
    public function testConvertFile()
    {
        $content   = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031
Released=Mon, 30 Jun 2014 17:55:58 +0200
Format=ASP
Type=

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; DefaultProperties

[DefaultProperties]

Comment=DefaultProperties
Browser=DefaultProperties
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=false
Beta=false
Win16=false
Win32=false
Win64=false
Frames=false
IFrames=false
Tables=false
Cookies=false
BackgroundSounds=false
JavaScript=false
VBScript=false
JavaApplets=false
ActiveXControls=false
isMobileDevice=false
isTablet=false
isSyndicationReader=false
Crawler=false
CssVersion=0
AolVersion=0

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Ask

[Ask]

Parent=DefaultProperties
Comment=Ask
Browser=Ask
Frames=1
IFrames=1
Tables=1
Crawler=1
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
CssVersion=0
AolVersion=0

[Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)]

Parent=Ask
Browser=Teoma
Comment=Ask
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Frames=1
IFrames=1
Tables=1
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
Crawler=1
CssVersion=0
AolVersion=0
';
        $structure = [
            'test.ini' => $content,
        ];

        vfsStream::setup(self::STORAGE_DIR, null, $structure);

        $cache = new Memory();
        $this->object->setCache($cache);

        $this->object->convertFile(vfsStream::url(self::STORAGE_DIR . DIRECTORY_SEPARATOR . 'test.ini'));

        self::assertSame(5031, $this->object->getCache()->getVersion());
    }

    /**
     *
     */
    public function testConvertString()
    {
        $content   = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031
Released=Mon, 30 Jun 2014 17:55:58 +0200
Format=ASP
Type=

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; DefaultProperties

[DefaultProperties]

Comment=DefaultProperties
Browser=DefaultProperties
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=false
Beta=false
Win16=false
Win32=false
Win64=false
Frames=false
IFrames=false
Tables=false
Cookies=false
BackgroundSounds=false
JavaScript=false
VBScript=false
JavaApplets=false
ActiveXControls=false
isMobileDevice=false
isTablet=false
isSyndicationReader=false
Crawler=false
CssVersion=0
AolVersion=0

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Ask

[Ask]

Parent=DefaultProperties
Comment=Ask
Browser=Ask
Frames=1
IFrames=1
Tables=1
Crawler=1
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
CssVersion=0
AolVersion=0

[Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)]

Parent=Ask
Browser=Teoma
Comment=Ask
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Frames=1
IFrames=1
Tables=1
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
Crawler=1
CssVersion=0
AolVersion=0
';

        $cache = new Memory();
        $this->object->setCache($cache);

        $this->object->convertString($content);

        self::assertSame(5031, $this->object->getCache()->getVersion());
    }

    /**
     * @expectedException \BrowscapPHP\Exception\FetcherException
     * @expectedExceptionMessage an error occured while fetching version data from URI http://browscap.org/version-number: StatusCode was 500
     */
    public function testFetchFail()
    {
        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->object->setLogger($logger);

        $response = $this->getMockBuilder(GuzzleHttp\Psr7\Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatusCode'])
            ->getMock();
        $response
            ->expects(self::exactly(2))
            ->method('getStatusCode')
            ->will(self::returnValue(500));

        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $client
            ->expects(self::once())
            ->method('get')
            ->will(self::returnValue($response));

        $this->object->setClient($client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                6000,
            ],
        ];

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'setItem'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValueMap($map));
        $cache
            ->expects(self::never())
            ->method('setItem')
            ->will(self::returnValue(false));

        $this->object->setCache($cache);

        $this->object->fetch(IniLoader::PHP_INI);
    }

    /**
     *
     */
    public function testFetchOK()
    {
        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->object->setLogger($logger);

        $content   = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031
Released=Mon, 30 Jun 2014 17:55:58 +0200
Format=ASP
Type=

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; DefaultProperties

[DefaultProperties]

Comment=DefaultProperties
Browser=DefaultProperties
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=false
Beta=false
Win16=false
Win32=false
Win64=false
Frames=false
IFrames=false
Tables=false
Cookies=false
BackgroundSounds=false
JavaScript=false
VBScript=false
JavaApplets=false
ActiveXControls=false
isMobileDevice=false
isTablet=false
isSyndicationReader=false
Crawler=false
CssVersion=0
AolVersion=0

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Ask

[Ask]

Parent=DefaultProperties
Comment=Ask
Browser=Ask
Frames=1
IFrames=1
Tables=1
Crawler=1
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
CssVersion=0
AolVersion=0

[Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)]

Parent=Ask
Browser=Teoma
Comment=Ask
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Frames=1
IFrames=1
Tables=1
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
Crawler=1
CssVersion=0
AolVersion=0
';

        $body = $this->getMockBuilder(\GuzzleHttp\Psr7\Stream::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContents'])
            ->getMock();
        $body
            ->expects(self::once())
            ->method('getContents')
            ->will(self::returnValue($content));

        $response = $this->getMockBuilder(\GuzzleHttp\Psr7\Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatusCode', 'getBody'])
            ->getMock();
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->will(self::returnValue(200));
        $response
            ->expects(self::once())
            ->method('getBody')
            ->will(self::returnValue($body));

        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $client
            ->expects(self::once())
            ->method('get')
            ->will(self::returnValue($response));

        $this->object->setClient($client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                null,
            ],
        ];

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'setItem'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValueMap($map));
        $cache
            ->expects(self::never())
            ->method('setItem')
            ->will(self::returnValue(false));

        $this->object->setCache($cache);

        $file = 'resources/test.ini';

        $this->object->fetch($file);

        self::assertSame($content, file_get_contents($file));
    }

    /**
     *
     */
    public function testFetchSanitizeOK()
    {
        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->object->setLogger($logger);

        $content = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031\'?><?php
Released=Mon, 30 Jun 2014 17:55:58 +0200\'?><?= exit(\'test\'); ?>
Format=ASP\'?><% exit(\'test\'); %>
Type=\'?><?php exit(\'\'); ?>

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; DefaultProperties

[DefaultProperties]

Comment=DefaultProperties
Browser=DefaultProperties
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=false
Beta=false
Win16=false
Win32=false
Win64=false
Frames=false
IFrames=false
Tables=false
Cookies=false
BackgroundSounds=false
JavaScript=false
VBScript=false
JavaApplets=false
ActiveXControls=false
isMobileDevice=false
isTablet=false
isSyndicationReader=false
Crawler=false
CssVersion=0
AolVersion=0

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Ask

[Ask]

Parent=DefaultProperties
Comment=Ask
Browser=Ask
Frames=1
IFrames=1
Tables=1
Crawler=1
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
CssVersion=0
AolVersion=0

[Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)]

Parent=Ask
Browser=Teoma
Comment=Ask
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Frames=1
IFrames=1
Tables=1
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
Crawler=1
CssVersion=0
AolVersion=0
';

        $expected = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031\'php
Released=Mon, 30 Jun 2014 17:55:58 +0200\'
Format=ASP\'
Type=\'

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; DefaultProperties

[DefaultProperties]

Comment=DefaultProperties
Browser=DefaultProperties
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=false
Beta=false
Win16=false
Win32=false
Win64=false
Frames=false
IFrames=false
Tables=false
Cookies=false
BackgroundSounds=false
JavaScript=false
VBScript=false
JavaApplets=false
ActiveXControls=false
isMobileDevice=false
isTablet=false
isSyndicationReader=false
Crawler=false
CssVersion=0
AolVersion=0

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Ask

[Ask]

Parent=DefaultProperties
Comment=Ask
Browser=Ask
Frames=1
IFrames=1
Tables=1
Crawler=1
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
CssVersion=0
AolVersion=0

[Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)]

Parent=Ask
Browser=Teoma
Comment=Ask
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Frames=1
IFrames=1
Tables=1
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
Crawler=1
CssVersion=0
AolVersion=0
';

        $body = $this->getMockBuilder(\GuzzleHttp\Psr7\Stream::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContents'])
            ->getMock();
        $body
            ->expects(self::once())
            ->method('getContents')
            ->will(self::returnValue($content));

        $response = $this->getMockBuilder(\GuzzleHttp\Psr7\Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatusCode', 'getBody'])
            ->getMock();
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->will(self::returnValue(200));
        $response
            ->expects(self::once())
            ->method('getBody')
            ->will(self::returnValue($body));

        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $client
            ->expects(self::once())
            ->method('get')
            ->will(self::returnValue($response));

        $this->object->setClient($client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                null,
            ],
        ];

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'setItem'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValueMap($map));
        $cache
            ->expects(self::never())
            ->method('setItem')
            ->will(self::returnValue(false));

        $this->object->setCache($cache);

        $this->object->fetch(IniLoader::PHP_INI);

        self::assertSame($expected, file_get_contents(IniLoader::PHP_INI));
    }

    /**
     * @expectedException \BrowscapPHP\Exception\FetcherException
     * @expectedExceptionMessage Could not fetch HTTP resource "http://browscap.org/stream?q=PHP_BrowscapINI":
     */
    public function testUpdateFailException()
    {
        if (class_exists('\Browscap\Browscap')) {
            self::markTestSkipped(
                'if the \Browscap\Browscap class is available the browscap.ini file is not updated from a remote '
                . 'location'
            );
        }

        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->object->setLogger($logger);

        $body = $this->getMockBuilder(\GuzzleHttp\Psr7\Stream::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContents'])
            ->getMock();
        $body
            ->expects(self::once())
            ->method('getContents')
            ->will(self::returnValue(false));

        $response = $this->getMockBuilder(\GuzzleHttp\Psr7\Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatusCode', 'getBody'])
            ->getMock();
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->will(self::returnValue(200));
        $response
            ->expects(self::once())
            ->method('getBody')
            ->will(self::returnValue($body));

        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $client
            ->expects(self::once())
            ->method('get')
            ->will(self::returnValue($response));

        $this->object->setClient($client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                null,
            ],
        ];

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'setItem'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValueMap($map));
        $cache
            ->expects(self::never())
            ->method('setItem')
            ->will(self::returnValue(true));

        $this->object->setCache($cache);

        $this->object->update();
    }

    /**
     *
     */
    public function testUpdateOk()
    {
        if (class_exists('\Browscap\Browscap')) {
            self::markTestSkipped(
                'if the \Browscap\Browscap class is available the browscap.ini file is not updated from a remote '
                . 'location'
            );
        }

        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->object->setLogger($logger);

        $content   = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031
Released=Mon, 30 Jun 2014 17:55:58 +0200
Format=ASP
Type=

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; DefaultProperties

[DefaultProperties]

Comment=DefaultProperties
Browser=DefaultProperties
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=false
Beta=false
Win16=false
Win32=false
Win64=false
Frames=false
IFrames=false
Tables=false
Cookies=false
BackgroundSounds=false
JavaScript=false
VBScript=false
JavaApplets=false
ActiveXControls=false
isMobileDevice=false
isTablet=false
isSyndicationReader=false
Crawler=false
CssVersion=0
AolVersion=0

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Ask

[Ask]

Parent=DefaultProperties
Comment=Ask
Browser=Ask
Frames=1
IFrames=1
Tables=1
Crawler=1
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
CssVersion=0
AolVersion=0

[Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)]

Parent=Ask
Browser=Teoma
Comment=Ask
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Frames=1
IFrames=1
Tables=1
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
Crawler=1
CssVersion=0
AolVersion=0
';

        $body = $this->getMockBuilder(\GuzzleHttp\Psr7\Stream::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContents'])
            ->getMock();
        $body
            ->expects(self::once())
            ->method('getContents')
            ->will(self::returnValue($content));

        $response = $this->getMockBuilder(\GuzzleHttp\Psr7\Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatusCode', 'getBody'])
            ->getMock();
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->will(self::returnValue(200));
        $response
            ->expects(self::once())
            ->method('getBody')
            ->will(self::returnValue($body));

        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $client
            ->expects(self::once())
            ->method('get')
            ->will(self::returnValue($response));

        $this->object->setClient($client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                null,
            ],
        ];

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'setItem'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValueMap($map));
        $cache
            ->expects(self::exactly(4355))
            ->method('setItem')
            ->will(self::returnValue(false));

        $this->object->setCache($cache);

        self::assertNull($this->object->update());
    }

    /**
     *
     */
    public function testCheckUpdateWithCacheFail()
    {
        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->object->setLogger($logger);

        $body = $this->getMockBuilder(\GuzzleHttp\Psr7\Stream::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContents'])
            ->getMock();
        $body
            ->expects(self::never())
            ->method('getContents')
            ->will(self::returnValue(false));

        $response = $this->getMockBuilder(\GuzzleHttp\Psr7\Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatusCode', 'getBody'])
            ->getMock();
        $response
            ->expects(self::never())
            ->method('getStatusCode')
            ->will(self::returnValue(200));
        $response
            ->expects(self::never())
            ->method('getBody')
            ->will(self::returnValue($body));

        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $client
            ->expects(self::never())
            ->method('get')
            ->will(self::returnValue($response));

        $this->object->setClient($client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                null,
            ],
        ];

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'setItem'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValueMap($map));
        $cache
            ->expects(self::never())
            ->method('setItem')
            ->will(self::returnValue(false));

        $this->object->setCache($cache);

        self::assertSame(0, $this->object->checkUpdate());
    }

    /**
     * @expectedException \BrowscapPHP\Exception\FetcherException
     * @expectedExceptionMessage an error occured while fetching version data from URI http://browscap.org/version-number: StatusCode was 200
     */
    public function testCheckUpdateWithException()
    {
        $version = 6000;

        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->object->setLogger($logger);

        $body = $this->getMockBuilder(\GuzzleHttp\Psr7\Stream::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContents'])
            ->getMock();
        $body
            ->expects(self::once())
            ->method('getContents')
            ->will(self::throwException(new Exception('Exception')));

        $response = $this->getMockBuilder(\GuzzleHttp\Psr7\Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatusCode', 'getBody'])
            ->getMock();
        $response
            ->expects(self::exactly(2))
            ->method('getStatusCode')
            ->will(self::returnValue(200));
        $response
            ->expects(self::once())
            ->method('getBody')
            ->will(self::returnValue($body));

        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $client
            ->expects(self::once())
            ->method('get')
            ->will(self::returnValue($response));

        $this->object->setClient($client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                $version,
            ],
        ];

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'setItem'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValueMap($map));
        $cache
            ->expects(self::never())
            ->method('setItem')
            ->will(self::returnValue(false));

        $this->object->setCache($cache);

        $this->object->checkUpdate();
    }

    /**
     *
     */
    public function testCheckUpdateWithoutNewerVersion()
    {
        $version = 6000;

        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->object->setLogger($logger);

        $body = $this->getMockBuilder(\GuzzleHttp\Psr7\Stream::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContents'])
            ->getMock();
        $body
            ->expects(self::once())
            ->method('getContents')
            ->will(self::returnValue($version));

        $response = $this->getMockBuilder(\GuzzleHttp\Psr7\Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatusCode', 'getBody'])
            ->getMock();
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->will(self::returnValue(200));
        $response
            ->expects(self::once())
            ->method('getBody')
            ->will(self::returnValue($body));

        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $client
            ->expects(self::once())
            ->method('get')
            ->will(self::returnValue($response));

        $this->object->setClient($client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                $version,
            ],
        ];

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'setItem'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getItem')
            ->will(self::returnValueMap($map));
        $cache
            ->expects(self::never())
            ->method('setItem')
            ->will(self::returnValue(false));

        $this->object->setCache($cache);

        self::assertNull($this->object->checkUpdate());
    }

    /**
     *
     */
    public function testCheckUpdateWithNewerVersion()
    {
        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->object->setLogger($logger);

        $body = $this->getMockBuilder(\GuzzleHttp\Psr7\Stream::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContents'])
            ->getMock();
        $body
            ->expects(self::once())
            ->method('getContents')
            ->will(self::returnValue(6001));

        $response = $this->getMockBuilder(\GuzzleHttp\Psr7\Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatusCode', 'getBody'])
            ->getMock();
        $response
            ->expects(self::once())
            ->method('getStatusCode')
            ->will(self::returnValue(200));
        $response
            ->expects(self::once())
            ->method('getBody')
            ->will(self::returnValue($body));

        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
        $client
            ->expects(self::once())
            ->method('get')
            ->will(self::returnValue($response));

        $this->object->setClient($client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                6000,
            ],
        ];

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItem', 'hasItem', 'setItem'])
            ->getMock();
        $cache
            ->expects(self::any())
            ->method('getItem')
            ->will(self::returnValueMap($map));
        $cache
            ->expects(self::any())
            ->method('hasItem')
            ->will(self::returnValue(true));
        $cache
            ->expects(self::never())
            ->method('setItem')
            ->will(self::returnValue(false));

        $this->object->setCache($cache);

        self::assertSame(6000, $this->object->checkUpdate());
    }
}
