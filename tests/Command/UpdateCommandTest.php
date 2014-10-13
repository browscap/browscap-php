<?php

namespace phpbrowscapTest\Command;

use phpbrowscap\Command\UpdateCommand;
use phpbrowscap\Cache\BrowscapCache;
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
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/GaretJax/phpbrowscap/
 */
class UpdateCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \phpbrowscap\Command\UpdateCommand
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

        $this->object = new UpdateCommand($cache);
    }

    /**
     *
     */
    public function testConfigure()
    {
        $object = $this->getMock(
            '\phpbrowscap\Command\UpdateCommand',
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
            ->expects(self::never())
            ->method('addArgument')
            ->will(self::returnSelf())
        ;
        $object
            ->expects(self::exactly(2))
            ->method('addOption')
            ->will(self::returnSelf())
        ;

        $class  = new \ReflectionClass('\phpbrowscap\Command\UpdateCommand');
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }

    /**
     *
     */
    public function testExecute()
    {
        self::markTestSkipped('not ready yet');

        $input  = $this->getMock('\Symfony\Component\Console\Input\ArgvInput', array(), array(), '', false);
        $output = $this->getMock('\Symfony\Component\Console\Output\ConsoleOutput', array(), array(), '', false);

        $class  = new \ReflectionClass('\phpbrowscap\Command\UpdateCommand');
        $method = $class->getMethod('execute');
        $method->setAccessible(true);

        self::assertNull($method->invoke($this->object, $input, $output));
    }
}
