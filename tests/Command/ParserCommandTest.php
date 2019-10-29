<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\ParserCommand;

final class ParserCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testConfigure() : void
    {
        $object = $this->getMockBuilder(ParserCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['setName', 'setDescription', 'addArgument', 'addOption'])
            ->getMock();
        $object
            ->expects(static::once())
            ->method('setName')
            ->willReturn($object);
        $object
            ->expects(static::once())
            ->method('setDescription')
            ->willReturnSelf();
        $object
            ->expects(static::once())
            ->method('addArgument')
            ->willReturnSelf();
        $object
            ->expects(static::once())
            ->method('addOption')
            ->willReturnSelf();

        $class = new \ReflectionClass(ParserCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        static::assertNull($method->invoke($object));
    }
}
