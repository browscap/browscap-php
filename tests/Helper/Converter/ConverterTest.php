<?php

namespace BrowscapPHPTest\Helper\Converter;

use BrowscapPHP\Helper\Converter;
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
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class ConverterTest extends \PHPUnit_Framework_TestCase
{
    const STORAGE_DIR = 'storage';

    /**
     * @var \BrowscapPHP\Helper\Converter
     */
    private $object = null;

    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $root = null;

    public function setUp()
    {
        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMock();
        $logger->expects(self::never())
               ->method('info')
               ->will(self::returnValue(false));

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['setItem'])
            ->getMock();
        $cache->expects(self::any())
              ->method('setItem')
              ->will(self::returnValue(true));

        $this->object = new Converter($logger, $cache);
    }

    /**
     *
     */
    public function testSetGetFilesystem()
    {
        self::assertInstanceOf('\BrowscapPHP\Helper\Filesystem', $this->object->getFilesystem());

        /** @var \BrowscapPHP\Helper\Filesystem $file */
        $file = $this->getMockBuilder(\BrowscapPHP\Helper\Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertSame($this->object, $this->object->setFilesystem($file));
        self::assertSame($file, $this->object->getFilesystem());
    }

    /**
     * @expectedException \BrowscapPHP\Exception\FileNotFoundException
     * @expectedExceptionMessage testFile
     */
    public function testConvertMissingFile()
    {
        $file = $this->getMockBuilder(\BrowscapPHP\Helper\Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock();
        $file->expects(self::once())
            ->method('exists')
            ->will(self::returnValue(false));

        $this->object->setFilesystem($file);
        $this->object->convertFile('testFile');
    }

    /**
     * @expectedException \BrowscapPHP\Exception\FileNotFoundException
     * @expectedExceptionMessage File "vfs://storage/test.ini" does not exist
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
            self::STORAGE_DIR => [
                'test.ini' => $content,
            ],
        ];

        $this->root = vfsStream::setup(self::STORAGE_DIR, null, $structure);

        $file = $this->getMockBuilder(\BrowscapPHP\Helper\Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock();
        $file->expects(self::once())
            ->method('exists')
            ->will(self::returnValue(false));

        $this->object->setFilesystem($file);
        $this->object->convertFile(vfsStream::url(self::STORAGE_DIR . DIRECTORY_SEPARATOR . 'test.ini'));
    }

    /**
     *
     */
    public function testGetIniVersion()
    {
        $file = $this->getMockBuilder(\BrowscapPHP\Helper\Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['exists'])
            ->getMock();
        $file->expects(self::never())
            ->method('exists')
            ->will(self::returnValue(false));

        $this->object->setFilesystem($file);

        $content = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031
Released=Mon, 30 Jun 2014 17:55:58 +0200
Format=ASP
Type=';

        self::assertSame(5031, $this->object->getIniVersion($content));
        self::assertSame($this->object, $this->object->storeVersion());
    }
}
