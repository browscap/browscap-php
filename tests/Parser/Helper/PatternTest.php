<?php

namespace BrowscapPHPTest\Parser\Helper;

use BrowscapPHP\Parser\Helper\Pattern;

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
 * @link       https://github.com/GaretJax/BrowscapPHP/
 */
class PatternTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testGetPatternCacheSubkey()
    {
        self::assertSame('ab', Pattern::getPatternCacheSubkey('abcd'));
    }

    /**
     *
     */
    public function testGetPatternStartWithoutVariants()
    {
        $pattern = '[Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)]

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
        self::assertSame('235cb78c730de50ce5ba6a0c1784b16b', Pattern::getPatternStart($pattern, false));
    }

    /**
     *
     */
    public function testGetPatternStartWithVariants()
    {
        $pattern = '[Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)]

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
        $expected = array(
            0 => '235cb78c730de50ce5ba6a0c1784b16b',
            1 => 'da1cf5a1bb225ddb155d34f2b0e24a2f',
            2 => '8a8cb4370f287109b2b04f733c9e0be8',
            3 => '31a3f71a58d7463916e992fe2e5e0636',
            4 => 'b15bdb2762b5359a3bcf114bf259dc4d',
            5 => '5e2a51bcd4ab81cc553588b044da6d1b',
            6 => '7d31a9d8363d6bc4d7d171bd9c0f032c',
            7 => 'ca66a7b1eb8df6a16330c46e4dee233b',
            8 => '815417267f76f6f460a4a61f9db75fdb',
            9 => 'd41d8cd98f00b204e9800998ecf8427e',
        );

        self::assertSame($expected, Pattern::getPatternStart($pattern, true));
    }

    /**
     *
     */
    public function testGetPatternLength()
    {
        self::assertSame(4, Pattern::getPatternLength('abcd'));
    }

    /**
     *
     */
    public function testGetAllPatternCacheSubkeys()
    {
        $result = Pattern::getAllPatternCacheSubkeys();
        self::assertInternalType('array', $result);
        self::assertSame(256, count($result));
    }
}
