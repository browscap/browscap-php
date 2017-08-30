<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\LoggerHelper;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \BrowscapPHP\Helper\LoggerHelper
 */
class LoggerHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate() : void
    {
        /** @var OutputInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $output = $this->createMock(OutputInterface::class);

        self::assertInstanceOf(Logger::class, LoggerHelper::createDefaultLogger($output));
    }
}
