<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\ParserCommand;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

final class ParserCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigure() : void
    {
        $object = $this->getMockBuilder(ParserCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['setName', 'setDescription', 'addArgument', 'addOption'])
            ->getMock();
        $object
            ->expects(self::once())
            ->method('setName')
            ->willReturn($object);
        $object
            ->expects(self::once())
            ->method('setDescription')
            ->willReturnSelf();
        $object
            ->expects(self::once())
            ->method('addArgument')
            ->willReturnSelf();
        $object
            ->expects(self::exactly(2))
            ->method('addOption')
            ->willReturnSelf();

        $class = new \ReflectionClass(ParserCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }
}
