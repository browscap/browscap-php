<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\UpdateCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * @covers \BrowscapPHP\Command\UpdateCommand
 */
final class UpdateCommandTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testConfigure(): void
    {
        $object = $this->getMockBuilder(UpdateCommand::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setName', 'setDescription', 'addArgument', 'addOption'])
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
            ->expects(self::never())
            ->method('addArgument')
            ->willReturnSelf();
        $object
            ->expects(self::exactly(3))
            ->method('addOption')
            ->willReturnSelf();

        $class  = new ReflectionClass(UpdateCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }
}
