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
            ->will(self::returnSelf());
        $object
            ->expects(self::once())
            ->method('setDescription')
            ->will(self::returnSelf());
        $object
            ->expects(self::once())
            ->method('addArgument')
            ->will(self::returnSelf());
        $object
            ->expects(self::once())
            ->method('addOption')
            ->will(self::returnSelf());

        $class = new \ReflectionClass(ConvertCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }
}
