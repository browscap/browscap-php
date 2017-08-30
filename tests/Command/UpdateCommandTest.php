<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\UpdateCommand;

/**
 * @covers \BrowscapPHP\Command\UpdateCommand
 */
final class UpdateCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigure() : void
    {
        $object = $this->getMockBuilder(UpdateCommand::class)
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
            ->expects(self::exactly(3))
            ->method('addOption')
            ->will(self::returnSelf());

        $class = new \ReflectionClass(UpdateCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }
}
