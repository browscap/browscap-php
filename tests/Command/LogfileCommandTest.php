<?php

namespace phpbrowscapTest\Command;

use phpbrowscap\Command\LogfileCommand;
use phpbrowscap\Cache\BrowscapCache;
use WurflCache\Adapter\Memory;
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
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/GaretJax/phpbrowscap/
 */
class LogfileCommandTest extends \PHPUnit_Framework_TestCase
{
    const STORAGE_DIR = 'storage';

    /**
     * @var \phpbrowscap\Command\LogfileCommand
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $cacheAdapter   = new Memory();
        $cache          = new BrowscapCache($cacheAdapter);

        $this->object = new LogfileCommand($cache);
    }

    /**
     *
     */
    public function testConfigure()
    {
        $object = $this->getMock(
            '\phpbrowscap\Command\LogfileCommand',
            array('setName', 'setDescription', 'addArgument', 'addOption'),
            array(),
            '',
            false
        );
        $object
            ->expects(self::once())
            ->method('setName')
            ->will(self::returnSelf())
        ;
        $object
            ->expects(self::once())
            ->method('setDescription')
            ->will(self::returnSelf())
        ;
        $object
            ->expects(self::once())
            ->method('addArgument')
            ->will(self::returnSelf())
        ;
        $object
            ->expects(self::exactly(5))
            ->method('addOption')
            ->will(self::returnSelf())
        ;

        $class  = new \ReflectionClass('\phpbrowscap\Command\LogfileCommand');
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }

    /**
     *
     */
    public function testExecute()
    {
        self::markTestSkipped('not ready yet');

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
            'test.log' => $content
        );

        vfsStream::setup(self::STORAGE_DIR, null, $structure);

        $map = array(
            array(
                'log-file',
                vfsStream::url(self::STORAGE_DIR . DIRECTORY_SEPARATOR . 'test.log')
            ),
            array(
                'debug',
                false
            ),
            array(
                'log-dir',
                null
            )
        );

        $input  = $this->getMock('\Symfony\Component\Console\Input\ArgvInput', array('getOption', 'getArgument'), array(), '', false);
        $input
            ->expects(self::exactly(5))
            ->method('getOption')
            ->will(self::returnValueMap($map))
        ;
        $input
            ->expects(self::once())
            ->method('getArgument')
            ->will(self::returnValue(vfsStream::url(self::STORAGE_DIR . DIRECTORY_SEPARATOR . 'test.txt')))
        ;

        $output = $this->getMock('\Symfony\Component\Console\Output\ConsoleOutput', array(), array(), '', false);

        $class  = new \ReflectionClass('\phpbrowscap\Command\LogfileCommand');
        $method = $class->getMethod('execute');
        $method->setAccessible(true);

        self::assertNull($method->invoke($this->object, $input, $output));
    }
}
