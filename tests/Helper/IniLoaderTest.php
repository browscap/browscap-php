<?php

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\IniLoader;

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
class IniLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BrowscapPHP\Helper\IniLoader
     */
    private $object = null;

    public function setUp()
    {
        $this->object = new IniLoader();
    }

    /**
     * @expectedException \BrowscapPHP\Helper\Exception
     * @expectedExceptionMessage the filename can not be empty
     */
    public function testSetMissingRemoteFilename()
    {
        self::assertSame($this->object, $this->object->setRemoteFilename());
    }

    /**
     *
     */
    public function testSetRemoteFilename()
    {
        self::assertSame($this->object, $this->object->setRemoteFilename('testFile'));
    }

    /**
     *
     */
    public function testGetRemoteIniUrl()
    {
        $this->object->setRemoteFilename(IniLoader::PHP_INI_LITE);
        self::assertSame('http://browscap.org/stream?q=Lite_PHP_BrowscapINI', $this->object->getRemoteIniUrl());

        $this->object->setRemoteFilename(IniLoader::PHP_INI);
        self::assertSame('http://browscap.org/stream?q=PHP_BrowscapINI', $this->object->getRemoteIniUrl());

        $this->object->setRemoteFilename(IniLoader::PHP_INI_FULL);
        self::assertSame('http://browscap.org/stream?q=Full_PHP_BrowscapINI', $this->object->getRemoteIniUrl());
    }

    /**
     *
     */
    public function testGetRemoteVerUrl()
    {
        self::assertSame('http://browscap.org/version', $this->object->getRemoteTimeUrl());
    }
}
