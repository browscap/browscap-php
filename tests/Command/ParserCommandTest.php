<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\ParserCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

final class ParserCommandTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testConfigure(): void
    {
        $object = $this->getMockBuilder(ParserCommand::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setName', 'setDescription', 'addArgument', 'addOption'])
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
            ->expects(self::once())
            ->method('addOption')
            ->willReturnSelf();

        $class  = new ReflectionClass(ParserCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }
}
