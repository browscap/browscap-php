<?php

namespace phpbrowscapTest\Helper;

use phpbrowscap\Helper\Sorter;

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
class SorterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \phpbrowscap\Helper\Sorter
     */
    private $sorter = null;

    public function setUp()
    {
        $this->sorter = new Sorter();
    }

    /**
     * @dataProvider dataCompareBcStrings
     */
    public function testCompareBcStrings($a, $b, $expected)
    {
        self::assertSame($expected, $this->sorter->compareBcStrings($a, $b));
    }

    public function dataCompareBcStrings()
    {
        return array(
            array('Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)', 'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)', 1),
            array('Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)', 'Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)', -1),
            array('Mozilla/5.0 (Danger hiptop 3.*; U; rv:1.7.*) Gecko/*', 'Mozilla/5.0 (Danger hiptop 3.0; U; rv:1.7.*) Gecko/*', 1),
            array('Mozilla/5.0 (Danger hiptop 3.0; U; rv:1.7.*) Gecko/*', 'Mozilla/5.0 (Danger hiptop 3.*; U; rv:1.7.*) Gecko/*', -1),
            array('Mozilla/5.0 (Danger hiptop 3.0; U; rv:1.7.*) Gecko/*', 'Mozilla/5.0 (Danger hiptop 3.0; U; rv:1.7.*) Gecko/*', 0)
        );
    }
}
