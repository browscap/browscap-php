<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\ConvertCommand;

/**
 * @covers \BrowscapPHP\Command\ConvertCommand
 */
final class ConvertCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigure() : void
    {
        $object = $this->getMockBuilder(ConvertCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['setName', 'setDescription', 'addArgument', 'addOption'])
            ->getMock();
        $object
            ->expects(self::once())
            ->method('setName')
            ->willReturnSelf();
        $object
            ->expects(self::once())
            ->method('setDescription')
            ->willReturnSelf();
        $object
            ->expects(self::once())
            ->method('addArgument')
            ->willReturnSelf();
        $object
            ->expects(self::once())
            ->method('addOption')
            ->willReturnSelf();

        $class = new \ReflectionClass(ConvertCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }
}
