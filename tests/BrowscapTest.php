<?php

namespace BrowscapPHPTest;

use BrowscapPHP\Browscap;
use BrowscapPHP\Helper\IniLoader;
use org\bovigo\vfs\vfsStream;

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
        /** @var \BrowscapPHP\Formatter\PhpGetBrowser $formatter */
        $formatter = $this->getMock('\BrowscapPHP\Formatter\PhpGetBrowser', array(), array(), '', false);

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
        $cache = $this->getMock('\BrowscapPHP\Cache\BrowscapCache', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setCache($cache));
        self::assertSame($cache, $this->object->getCache());
    }

    /**
     *
     */
    public function testSetGetCacheWithAdapter()
    {
        /** @var \WurflCache\Adapter\Memory $cache */
        $cache = $this->getMock('\WurflCache\Adapter\Memory', array(), array(), '', false);

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
        $parser = $this->getMock('\BrowscapPHP\Parser\Ini', array(), array(), '', false);

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

    /**
     *
     */
    public function testGetBrowserWithoutUa()
    {
        $browserObject = new \StdClass();
        $browserObject->parent = 'something';
        $browserObject->comment = 'an comment';

        $formatter = $this->getMock('\BrowscapPHP\Formatter\PhpGetBrowser', array('getData'), array(), '', false);
        $formatter
            ->expects(self::once())
            ->method('getData')
            ->will(self::returnValue($browserObject))
        ;

        $parser = $this->getMock('\BrowscapPHP\Parser\Ini', array('getBrowser'), array(), '', false);
        $parser
            ->expects(self::once())
            ->method('getBrowser')
            ->will(self::returnValue($formatter))
        ;

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);
        $result = $this->object->getBrowser();

        self::assertSame($browserObject, $result);
    }

    /**
     *
     */
    public function testGetBrowserWithUa()
    {
        $browserObject = new \StdClass();
        $browserObject->parent = 'something';
        $browserObject->comment = 'an comment';

        $formatter = $this->getMock('\BrowscapPHP\Formatter\PhpGetBrowser', array('getData'), array(), '', false);
        $formatter
            ->expects(self::once())
            ->method('getData')
            ->will(self::returnValue($browserObject))
        ;

        $parser = $this->getMock('\BrowscapPHP\Parser\Ini', array('getBrowser'), array(), '', false);
        $parser
            ->expects(self::once())
            ->method('getBrowser')
            ->will(self::returnValue($formatter))
        ;

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);
        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        self::assertSame($browserObject, $result);
    }

    /**
     *
     */
    public function testGetBrowserWithDefaultResult()
    {
        $formatter = $this->getMock('\BrowscapPHP\Formatter\PhpGetBrowser', array('getData'), array(), '', false);
        $formatter
            ->expects(self::once())
            ->method('getData')
            ->will(self::returnValue(null))
        ;

        $parser = $this->getMock('\BrowscapPHP\Parser\Ini', array('getBrowser'), array(), '', false);
        $parser
            ->expects(self::once())
            ->method('getBrowser')
            ->will(self::returnValue(null))
        ;

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);
        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        self::assertNull($result);
    }

    /**
     * tests if an exception is thrown if no file name was given
     *
     * @expectedException \BrowscapPHP\Exception
     * @expectedExceptionMessage an error occured while setting the local file
     */
    public function testConvertEmptyFile()
    {
        $this->object->convertFile(null);
    }

    /**
     * tests if an exception is thrown if no file name was given
     *
     * @expectedException \BrowscapPHP\Exception
     * @expectedExceptionMessage an error occured while converting the local file into the cache
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
        $structure = array(
            'test.ini' => $content
        );

        vfsStream::setup(self::STORAGE_DIR, null, $structure);

        $cache = $this->getMock('\WurflCache\Adapter\Memory', array(), array(), '', false);
        $this->object->setCache($cache);

        self::assertNull(
            $this->object->convertFile(vfsStream::url(self::STORAGE_DIR . DIRECTORY_SEPARATOR . 'test.ini'))
        );
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

        $cache = $this->getMock('\WurflCache\Adapter\Memory', array(), array(), '', false);
        $this->object->setCache($cache);

        self::assertNull(
            $this->object->convertString($content)
        );
    }

    /**
     * @expectedException \BrowscapPHP\Exception\FetcherException
     * @expectedExceptionMessage Could not fetch HTTP resource "http://browscap.org/stream?q=PHP_BrowscapINI":
     */
    public function testFetchFail()
    {
        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);
        $this->object->setLogger($logger);

        $loader = $this->getMock('\BrowscapPHP\Helper\IniLoader', array('setRemoteFilename', 'setOptions', 'setLogger', 'load'), array(), '', false);
        $loader
            ->expects(self::exactly(2))
            ->method('setRemoteFilename')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::exactly(2))
            ->method('setOptions')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::exactly(2))
            ->method('setLogger')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('load')
            ->will(self::returnValue(false))
        ;

        $this->object->setLoader($loader);

        $map = array(
            array(
                'browscap.time',
                false,
                null,
                null
            ),
            array(
                'browscap.version',
                false,
                null,
                6000
            ),
        );

        $cache = $this->getMock('\BrowscapPHP\Cache\BrowscapCache', array('getItem'), array(), '', false);
        $cache
            ->expects(self::any())
            ->method('getItem')
            ->will(self::returnValueMap($map))
        ;

        $this->object->setCache($cache);

        $this->object->fetch(IniLoader::PHP_INI);
    }

    /**
     *
     */
    public function testFetchOK()
    {
        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);
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

        $loader = $this->getMock('\BrowscapPHP\Helper\IniLoader', array('setRemoteFilename', 'setOptions', 'setLogger', 'load'), array(), '', false);
        $loader
            ->expects(self::once())
            ->method('setRemoteFilename')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('setOptions')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('setLogger')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('load')
            ->will(self::returnValue($content))
        ;

        $this->object->setLoader($loader);

        $map = array(
            array(
                'browscap.time',
                false,
                null,
                null
            ),
            array(
                'browscap.version',
                false,
                null,
                null
            ),
        );

        $cache = $this->getMock('\BrowscapPHP\Cache\BrowscapCache', array('getItem'), array(), '', false);
        $cache
            ->expects(self::any())
            ->method('getItem')
            ->will(self::returnValueMap($map))
        ;

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
        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);
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

        $loader = $this->getMock(
            '\BrowscapPHP\Helper\IniLoader',
            array('setRemoteFilename', 'setOptions', 'setLogger', 'load'),
            array(),
            '',
            false
        );
        $loader
            ->expects(self::once())
            ->method('setRemoteFilename')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('setOptions')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('setLogger')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('load')
            ->will(self::returnValue($content))
        ;

        $this->object->setLoader($loader);

        $map = array(
            array(
                'browscap.time',
                false,
                null,
                null
            ),
            array(
                'browscap.version',
                false,
                null,
                null
            ),
        );

        $cache = $this->getMock('\BrowscapPHP\Cache\BrowscapCache', array('getItem'), array(), '', false);
        $cache
            ->expects(self::any())
            ->method('getItem')
            ->will(self::returnValueMap($map))
        ;

        $this->object->setCache($cache);

        $this->object->fetch(IniLoader::PHP_INI);

        self::assertSame($expected, file_get_contents(IniLoader::PHP_INI));
    }

    /**
     * @expectedException \BrowscapPHP\Exception\FetcherException
     * @expectedExceptionMessage Could not fetch HTTP resource "http://browscap.org/stream?q=PHP_BrowscapINI":
     */
    public function testUpdate()
    {
        if (class_exists('\Browscap\Browscap')) {
            self::markTestSkipped(
                'if the \Browscap\Browscap class is available the browscap.ini file is not updated from a remote '
                . 'location'
            );
        }

        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);
        $this->object->setLogger($logger);

        $internalLoader = $this->getMock(
            '\FileLoader\Loader',
            array('getUri'),
            array(),
            '',
            false
        );
        $internalLoader
            ->expects(self::once())
            ->method('getUri')
            ->will(self::returnValue('http://browscap.org/stream?q=PHP_BrowscapINI'))
        ;

        $loader = $this->getMock(
            '\BrowscapPHP\Helper\IniLoader',
            array('setRemoteFilename', 'setOptions', 'setLogger', 'load', 'getLoader'),
            array(),
            '',
            false
        );
        $loader
            ->expects(self::once())
            ->method('setRemoteFilename')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('setOptions')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('setLogger')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('load')
            ->will(self::returnValue(false))
        ;
        $loader
            ->expects(self::once())
            ->method('getLoader')
            ->will(self::returnValue($internalLoader))
        ;

        $this->object->setLoader($loader);

        $map = array(
            array(
                'browscap.time',
                false,
                null,
                null
            ),
            array(
                'browscap.version',
                false,
                null,
                null
            ),
        );

        $cache = $this->getMock('\BrowscapPHP\Cache\BrowscapCache', array('getItem'), array(), '', false);
        $cache
            ->expects(self::any())
            ->method('getItem')
            ->will(self::returnValueMap($map))
        ;

        $this->object->setCache($cache);

        $this->object->update();
    }

    /**
     *
     */
    public function testCheckUpdateWithCacheFail()
    {
        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);
        $this->object->setLogger($logger);

        $loader = $this->getMock(
            '\BrowscapPHP\Helper\IniLoader',
            array('setRemoteFilename', 'setOptions', 'setLogger', 'load'),
            array(),
            '',
            false
        );
        $loader
            ->expects(self::never())
            ->method('setRemoteFilename')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::never())
            ->method('setOptions')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::never())
            ->method('setLogger')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::never())
            ->method('load')
            ->will(self::returnValue(false))
        ;

        $this->object->setLoader($loader);

        $map = array(
            array(
                'browscap.time',
                false,
                null,
                null
            ),
            array(
                'browscap.version',
                false,
                null,
                null
            ),
        );

        $cache = $this->getMock('\BrowscapPHP\Cache\BrowscapCache', array('getItem'), array(), '', false);
        $cache
            ->expects(self::any())
            ->method('getItem')
            ->will(self::returnValueMap($map))
        ;

        $this->object->setCache($cache);

        self::assertSame(0, $this->object->checkUpdate());
    }

    /**
     *
     */
    public function testCheckUpdateWithoutNewerVersion()
    {
        $version = 6000;

        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);
        $this->object->setLogger($logger);

        $loader = $this->getMock(
            '\BrowscapPHP\Helper\IniLoader',
            array('setRemoteFilename', 'setOptions', 'setLogger', 'load', 'getRemoteVersion'),
            array(),
            '',
            false
        );
        $loader
            ->expects(self::once())
            ->method('setRemoteFilename')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('setOptions')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('setLogger')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::never())
            ->method('load')
            ->will(self::returnValue(false))
        ;
        $loader
            ->expects(self::once())
            ->method('getRemoteVersion')
            ->will(self::returnValue($version))
        ;

        $this->object->setLoader($loader);

        $map = array(
            array(
                'browscap.time',
                false,
                null,
                null
            ),
            array(
                'browscap.version',
                false,
                null,
                $version
            ),
        );

        $cache = $this->getMock('\BrowscapPHP\Cache\BrowscapCache', array('getItem'), array(), '', false);
        $cache
            ->expects(self::any())
            ->method('getItem')
            ->will(self::returnValueMap($map))
        ;

        $this->object->setCache($cache);

        self::assertNull($this->object->checkUpdate());
    }

    /**
     *
     */
    public function testCheckUpdateWithNewerVersion()
    {
        $logger = $this->getMock('\Monolog\Logger', array(), array(), '', false);
        $this->object->setLogger($logger);

        $loader = $this->getMock(
            '\BrowscapPHP\Helper\IniLoader',
            array('setRemoteFilename', 'setOptions', 'setLogger', 'load', 'getRemoteVersion'),
            array(),
            '',
            false
        );
        $loader
            ->expects(self::once())
            ->method('setRemoteFilename')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('setOptions')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::once())
            ->method('setLogger')
            ->will(self::returnSelf())
        ;
        $loader
            ->expects(self::never())
            ->method('load')
            ->will(self::returnValue(false))
        ;
        $loader
            ->expects(self::once())
            ->method('getRemoteVersion')
            ->will(self::returnValue(6001))
        ;

        $this->object->setLoader($loader);

        $cache = $this->getMock('\BrowscapPHP\Cache\BrowscapCache', array('getItem', 'hasItem'), array(), '', false);
        $cache
            ->expects(self::any())
            ->method('getItem')
            ->will(self::returnValue(6000))
        ;
        $cache
            ->expects(self::any())
            ->method('hasItem')
            ->will(self::returnValue(true))
        ;

        $this->object->setCache($cache);

        self::assertSame(6000, $this->object->checkUpdate());
    }
}
