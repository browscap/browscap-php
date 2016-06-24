<?php

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\ParserCommand;

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
 * @group      command
 */
class ParserCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testConfigure()
    {
        $object = $this->getMockBuilder(\BrowscapPHP\Command\ParserCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['setName', 'setDescription', 'addArgument', 'addOption'])
            ->getMock();
        $object
            ->expects(self::once())
            ->method('setName')
            ->will(self::returnSelf());
        $object
            ->expects(self::once())
            ->method('setDescription')
            ->will(self::returnSelf());
        $object
            ->expects(self::once())
            ->method('addArgument')
            ->will(self::returnSelf());
        $object
            ->expects(self::exactly(2))
            ->method('addOption')
            ->will(self::returnSelf());

        $class  = new \ReflectionClass('\BrowscapPHP\Command\ParserCommand');
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVersion'])
            ->getMock();
        $cache
            ->expects(self::never())
            ->method('getVersion')
            ->will(self::returnValue(1));
        $object->setCache($cache);

        self::assertNull($method->invoke($object));
    }

    /**
     *
     */
    public function testExecute()
    {
        $object = new ParserCommand('');

        $cache = $this->getMockBuilder(\BrowscapPHP\Cache\BrowscapCache::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVersion', 'hasItem'])
            ->getMock();
        $cache
            ->expects(self::once())
            ->method('getVersion')
            ->will(self::returnValue(1));
        $cache
            ->expects(self::exactly(2))
            ->method('hasItem')
            ->will(self::returnValue(false));
        $object->setCache($cache);

        $input  = $this->getMockBuilder(\Symfony\Component\Console\Input\ArgvInput::class)
            ->disableOriginalConstructor()
            ->getMock();
        $output = $this->getMockBuilder(\Symfony\Component\Console\Output\ConsoleOutput::class)
            ->disableOriginalConstructor()
            ->getMock();

        $class  = new \ReflectionClass('\BrowscapPHP\Command\ParserCommand');
        $method = $class->getMethod('execute');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object, $input, $output));
    }
}
