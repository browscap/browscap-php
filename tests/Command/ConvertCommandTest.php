<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\ConvertCommand;

final class ConvertCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testConfigure() : void
    {
        $object = $this->getMockBuilder(ConvertCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['setName', 'setDescription', 'addArgument', 'addOption'])
            ->getMock();
        $object
            ->expects(static::once())
            ->method('setName')
            ->willReturnSelf();
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

        $class = new \ReflectionClass(ConvertCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        static::assertNull($method->invoke($object));
    }
}
