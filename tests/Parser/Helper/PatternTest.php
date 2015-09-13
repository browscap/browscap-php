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
     * @group pattern
     */
    public function testGetPatternStartWithoutVariants()
    {
        $pattern = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.68 Safari/537.36';
        self::assertSame('80ec954e53cf0878f566a0390e1c6b7b', Pattern::getHashForPattern(strtolower($pattern), false));
    }

    /**
     * @group pattern
     */
    public function testGetPatternStartWithVariants()
    {
        $pattern = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.68 Safari/537.36';
        $expected = array (
            0  => '80ec954e53cf0878f566a0390e1c6b7b',
            1  => 'e2b019273cd71e36d9f6da01bd16fe86',
            2  => 'aaa556aeec36ac3edfe2f5deea5f1d28',
            3  => '31d050fd7a4ea6c972063ef30d18991a',
            4  => 'dbeb1c32b66fd7717de583d999f89ec3',
            5  => '13e6ce11d0a70e2a5a3df41bf11d493e',
            6  => '3a4a9ff7cf86e273442bad1305f3d1fd',
            7  => 'b70924c16a59b9cc2de329464b64118e',
            8  => '89364cb625249b3d478bace02699e05d',
            9  => '27c9d5187cd283f8d160ec1ed2b5ac89',
            10 => '6f8f57715090da2632453988d9a1501b',
            11 => 'd41d8cd98f00b204e9800998ecf8427e',
        );

        self::assertSame($expected, Pattern::getHashForPattern(strtolower($pattern), true));
    }

    /**
     * @group pattern
     */
    public function testGetPatternLength()
    {
        self::assertSame(4, Pattern::getPatternLength('abcd'));
    }

    /**
     * @group pattern
     */
    public function testGetHashForParts()
    {
        self::assertSame(
            '529f1ddb64ea27d5cc6fc8ce8048d9e7',
            Pattern::getHashForParts('mozilla/5.0 (*linux i686*rv:0.9*) gecko*')
        );
    }
}
