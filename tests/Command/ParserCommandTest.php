<?php
declare(strict_types=1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Command\ParserCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

final class ParserCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigure() : void
    {
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache
            ->expects(self::never())
            ->method('getVersion')
            ->willReturn(1);

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

        $class  = new \ReflectionClass(ParserCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }

    public function testExecute() : void
    {
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache
            ->expects(self::once())
            ->method('getVersion')
            ->will(self::returnValue(1));
        $cache
            ->expects(self::exactly(2))
            ->method('hasItem')
            ->will(self::returnValue(false));

        $object = new ParserCommand('', $cache);

        $input  = $this->getMockBuilder(ArgvInput::class)
            ->disableOriginalConstructor()
            ->getMock();
        $output = $this->getMockBuilder(ConsoleOutput::class)
            ->disableOriginalConstructor()
            ->getMock();

        $class  = new \ReflectionClass(ParserCommand::class);
        $method = $class->getMethod('execute');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object, $input, $output));
    }
}
