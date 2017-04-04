<?php
declare(strict_types=1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Command\CheckUpdateCommand;
use WurflCache\Adapter\Memory;

final class CheckUpdateCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BrowscapPHP\Command\UpdateCommand
     */
    private $object;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp() : void
    {
        $cacheAdapter = new Memory();
        $cache = new BrowscapCache($cacheAdapter);

        $this->object = new CheckUpdateCommand('', $cache);
    }

    public function testConfigure() : void
    {
        $object = $this->getMockBuilder(CheckUpdateCommand::class)
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
            ->expects(self::never())
            ->method('addArgument')
            ->will(self::returnSelf());
        $object
            ->expects(self::exactly(2))
            ->method('addOption')
            ->will(self::returnSelf());

        $class = new \ReflectionClass(CheckUpdateCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }
}
