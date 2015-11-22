<?php

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Command\ParserCommand;
use WurflCache\Adapter\Memory;

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
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @group      command
 */
class ParserCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BrowscapPHP\Command\ParserCommand
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

        $this->object = new ParserCommand($cache);
    }

    /**
     *
     */
    public function testConfigure()
    {
        $object = $this->getMock(
            '\BrowscapPHP\Command\ParserCommand',
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
            ->expects(self::once())
            ->method('addOption')
            ->will(self::returnSelf())
        ;

        $class  = new \ReflectionClass('\BrowscapPHP\Command\ParserCommand');
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }

    /**
     *
     */
    public function testExecute()
    {
        $input  = $this->getMock('\Symfony\Component\Console\Input\ArgvInput', array(), array(), '', false);
        $output = $this->getMock('\Symfony\Component\Console\Output\ConsoleOutput', array(), array(), '', false);

        $class  = new \ReflectionClass('\BrowscapPHP\Command\ParserCommand');
        $method = $class->getMethod('execute');
        $method->setAccessible(true);

        self::assertNull($method->invoke($this->object, $input, $output));
    }
}
