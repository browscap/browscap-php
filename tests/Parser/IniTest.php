<?php

namespace BrowscapPHPTest\Parser;

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
 * @package    Browscap
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/GaretJax/BrowscapPHP/
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
     *
     */
    public function setUp()
    {
        /** @var \BrowscapPHP\Parser\Helper\GetPattern $helper */
        $patternHelper = $this->getMock('\BrowscapPHP\Parser\Helper\GetPattern', array(), array(), '', false);

        /** @var \BrowscapPHP\Parser\Helper\GetPattern $helper */
        $dataHelper = $this->getMock('\BrowscapPHP\Parser\Helper\GetData', array(), array(), '', false);

        /** @var \BrowscapPHP\Formatter\PhpGetBrowser $formatter */
        $formatter = $this->getMock('\BrowscapPHP\Formatter\PhpGetBrowser', array(), array(), '', false);

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
