<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\LoggerHelper;
use Monolog\Logger;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

/** @covers \BrowscapPHP\Helper\LoggerHelper */
class LoggerHelperTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testCreate(): void
    {
        $output = $this->createMock(OutputInterface::class);

        self::assertInstanceOf(Logger::class, LoggerHelper::createDefaultLogger($output));
    }
}
