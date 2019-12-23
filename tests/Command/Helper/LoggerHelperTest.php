<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Command\Helper;

use BrowscapPHP\Command\Helper\LoggerHelper;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;

final class LoggerHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LoggerHelper
     */
    private $helper;

    protected function setUp() : void
    {
        $this->helper = new LoggerHelper();
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testGetName() : void
    {
        self::assertSame('logger', $this->helper->getName());
    }

    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testCreate() : void
    {
        /** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject $output */
        $output = $this->createMock(OutputInterface::class);

        self::assertInstanceOf(Logger::class, $this->helper->build($output));
    }
}
