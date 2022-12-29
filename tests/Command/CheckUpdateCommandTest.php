<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\CheckUpdateCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

final class CheckUpdateCommandTest extends TestCase
{
    /** @throws ReflectionException */
    public function testConfigure(): void
    {
        $object = $this->getMockBuilder(CheckUpdateCommand::class)
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
            ->expects(self::once())
            ->method('addOption')
            ->willReturnSelf();

        $class  = new ReflectionClass(CheckUpdateCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }
}
