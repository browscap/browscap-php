<?php

namespace BrowscapPHPTest\Formatter;

use BrowscapPHP\Formatter\LegacyFormatter;

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
class LegacyFormatterTest extends \PHPUnit_Framework_TestCase
{
    public function formatterOptionsProvider()
    {
        return [
            [
                [],
                (object) [
                    'Browser' => 'test',
                    'Comment' => 'TestComment',
                ],
            ],
            [
                ['lowercase' => true],
                (object) [
                    'browser' => 'test',
                    'comment' => 'TestComment',
                ],
            ],
        ];
    }

    /**
     * @dataProvider formatterOptionsProvider
     * @param array     $options
     * @param \stdClass $expectedResult
     */
    public function testSetGetData($options, $expectedResult)
    {
        $data = [
            'Browser' => 'test',
            'Comment' => 'TestComment',
        ];

        $formatter = new LegacyFormatter($options);
        self::assertSame($formatter, $formatter->setData($data));
        $return = $formatter->getData();
        self::assertEquals($expectedResult, $return);
    }
}
