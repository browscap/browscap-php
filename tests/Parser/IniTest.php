<?php

namespace BrowscapPHPTest\Parser;

use BrowscapPHP\Parser\Helper\GetData;
use BrowscapPHP\Parser\Helper\GetPattern;
use BrowscapPHP\Parser\Ini;

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
class IniTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BrowscapPHP\Parser\Ini
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \Monolog\Logger $logger */
        $logger = $this->getMockBuilder(\Monolog\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \BrowscapPHP\Helper\Quoter $quoter */
        $quoter = $this->getMockBuilder(\BrowscapPHP\Helper\Quoter::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \BrowscapPHP\Parser\Helper\GetPattern $patternHelper */
        $patternHelper = new GetPattern($cache, $logger);

        /** @var \BrowscapPHP\Parser\Helper\GetData $dataHelper */
        $dataHelper = new GetData($cache, $logger, $quoter);

        /** @var \BrowscapPHP\Formatter\PhpGetBrowser $formatter */
        $formatter = $this->getMockBuilder(\BrowscapPHP\Formatter\PhpGetBrowser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new Ini($patternHelper, $dataHelper, $formatter);
    }

    /**
     *
     */
    public function testSetGetCache()
    {
        self::markTestSkipped('need to be deleted');
    }
}
