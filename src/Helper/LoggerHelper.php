<?php
declare(strict_types = 1);

namespace BrowscapPHP\Helper;

use Monolog\ErrorHandler;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LoggerHelper
 */
final class LoggerHelper
{
    private function __construct()
    {
    }

    /**
     * creates a \Monolog\Logger instance
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return LoggerInterface
     */
    public static function createDefaultLogger(OutputInterface $output) : LoggerInterface
    {
        $logger = new Logger('browscap');
        $consoleLogger = new ConsoleLogger($output);
        $psrHandler = new PsrHandler($consoleLogger);

        $logger->pushHandler($psrHandler);

        /** @var callable $memoryProcessor */
        $memoryProcessor = new MemoryUsageProcessor(true);
        $logger->pushProcessor($memoryProcessor);

        /** @var callable $peakMemoryProcessor */
        $peakMemoryProcessor = new MemoryPeakUsageProcessor(true);
        $logger->pushProcessor($peakMemoryProcessor);

        ErrorHandler::register($logger);

        return $logger;
    }
}
