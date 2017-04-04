<?php
declare(strict_types=1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\FetchCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @covers \BrowscapPHP\Command\FetchCommand
 */
final class FetchCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FetchCommand
     */
    private $object;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp() : void
    {
        $defaultIniFile = 'resources/browscap.ini';

        $this->object = new FetchCommand($defaultIniFile);
    }

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

    public function testExecute() : void
    {
        self::markTestSkipped('not ready yet');

        $input = $this->getMockBuilder(ArgvInput::class)
            ->disableOriginalConstructor()
            ->getMock();
        $output = $this->getMockBuilder(ConsoleOutput::class)
            ->disableOriginalConstructor()
            ->getMock();

        $class = new \ReflectionClass(FetchCommand::class);
        $method = $class->getMethod('execute');
        $method->setAccessible(true);

        self::assertNull($method->invoke($this->object, $input, $output));
    }
}
