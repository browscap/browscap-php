<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\FetchCommand;

/**
 * @covers \BrowscapPHP\Command\FetchCommand
 */
final class FetchCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigure() : void
    {
        $object = $this->getMockBuilder(FetchCommand::class)
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
            ->expects(self::exactly(2))
            ->method('addOption')
            ->will(self::returnSelf());

        $class = new \ReflectionClass(FetchCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }
}
