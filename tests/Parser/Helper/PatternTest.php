<?php

namespace phpbrowscapTest\Parser\Helper;

use phpbrowscap\Parser\Helper\Pattern;

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
        $pattern = '
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
        self::assertSame('9f682ab8d08d29dcfdf903a551041532', Pattern::getPatternStart($pattern, false));
    }

    /**
     *
     */
    public function testGetPatternStartWithVariants()
    {
        $pattern = '
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
        $expected = array(
            0 => '9f682ab8d08d29dcfdf903a551041532',
            1 => '61eb5b9b5c3640831a939f1505172d99',
            2 => '56be64f42fc5a64043d50b644bfbe8e4',
            3 => 'a17e2d50e840101cdef9513380e58eb0',
            4 => '102babdb315f476a94926677ee75bc33',
            5 => '7bc6b077149128faa5f60505ddf66d8b',
            6 => '207725bbff292458dc1f27e6380612fb',
            7 => 'cf25ada4ee8c919c1071425479828203',
            8 => '0f5968938d2fc124104f26d532ab2d2f',
            9 => '0bb89e443bf4c740849694c366053cc7',
            10 => 'cf76182e25e0f305059ad0fa5c1f2903',
            11 => '8343f8c706352e84b1c4cc3d965504b3',
            12 => 'bcd30809b0c5f5a6639e00f99b76bcc3',
            13 => '4a3d660c83be161e246e81b6a6f0e759',
            14 => '61e36827a465361f623252fa60fc99db',
            15 => 'ccbb20a39f769b076d0805e6de6f11dc',
            16 => 'e8f791e6dfaae20752deb76914987d23',
            17 => '9ab0469979ef2ad462a9c631f7bee57b',
            18 => '0d77ae958ea79ceafd3b6325cead40e8',
            19 => 'dcf79d48fe1c9598195420085aef8d42',
            20 => 'e444c37c98d06c78a9bc2bec775dfb83',
            21 => '5d2a30213544008af9f5619c817cb951',
            22 => '0b150dcec87b82e86c490e153307b609',
            23 => 'e1ac479bafd3b110495c5d7a7b18f345',
            24 => 'fe5d28debaffe4832cdc2b97693b7b3c',
            25 => 'e7d2873468242b93c68aae3fa964b0a3',
            26 => '1c4a35525b18fb4e85f9a10a2daee756',
            27 => 'afb0c413d29e59f172554e1fa13f8d73',
            28 => 'e7508ba7ac4f76500744676ac508db24',
            29 => '95dd19e22eecd28e59bfb997142aca0c',
            30 => '81051bcc2cf1bedf378224b0a93e2877',
            31 => 'dcb9be2f604e5df91deb9659bed4748d',
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
