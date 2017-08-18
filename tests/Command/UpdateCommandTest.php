<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\UpdateCommand;
use Doctrine\Common\Cache\ArrayCache;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @covers \BrowscapPHP\Command\UpdateCommand
 */
final class UpdateCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UpdateCommand
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp() : void
    {
        $memoryCache = new ArrayCache();
        $cache = new SimpleCacheAdapter($memoryCache);

        $this->object = new UpdateCommand('', $cache);
    }

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
            ->expects(self::exactly(4))
            ->method('addOption')
            ->will(self::returnSelf());

        $class = new \ReflectionClass(UpdateCommand::class);
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

        $class = new \ReflectionClass(UpdateCommand::class);
        $method = $class->getMethod('execute');
        $method->setAccessible(true);

        self::assertNull($method->invoke($this->object, $input, $output));
    }
}
