<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Command\ConvertCommand;
use BrowscapPHP\Exception as BrowscapException;
use Doctrine\Common\Cache\ArrayCache;
use Psr\Log\NullLogger;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @covers \BrowscapPHP\Command\ConvertCommand
 */
final class ConvertCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConvertCommand
     */
    private $object;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp() : void
    {
        $memoryCache = new ArrayCache();
        $cache = new SimpleCacheAdapter($memoryCache);

        $defaultIniFile = 'resources/browscap.ini';

        $logger = new NullLogger();

        $this->object = $this->getMockBuilder(ConvertCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLogger', 'getCache'])
            ->setConstructorArgs(['', $defaultIniFile, $cache])
            ->getMock();
        $this->object
            ->expects(self::any())
            ->method('getLogger')
            ->will(self::returnValue($logger));
        $this->object
            ->expects(self::any())
            ->method('getCache')
            ->will(self::returnValue($cache));
    }

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
            ->expects(self::exactly(2))
            ->method('addOption')
            ->will(self::returnSelf());

        $class = new \ReflectionClass(ConvertCommand::class);
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
        $objectIniPath = ini_get('browscap');

        if (! is_file($objectIniPath)) {
            $this->expectException(BrowscapException::class);
            $this->expectExceptionMessage('it was not possible to read the local file resources/browscap.ini');
        } else {
            $input
                ->expects(self::exactly(2))
                ->method('getArgument')
                ->with('file')
                ->will(self::returnValue($objectIniPath));
        }

        $map = [
            [
                'debug',
                false,
            ],
            [
                'cache',
                '/resources/',
            ],
        ];

        $input
            ->expects(self::any())
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $output = $this->getMockBuilder(ConsoleOutput::class)
            ->disableOriginalConstructor()
            ->getMock();

        $class = new \ReflectionClass(ConvertCommand::class);
        $method = $class->getMethod('execute');
        $method->setAccessible(true);

        self::assertNull($method->invoke($this->object, $input, $output));
    }
}
