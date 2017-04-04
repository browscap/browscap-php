<?php
declare(strict_types = 1);

namespace BrowscapPHP\Helper;

use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Psr\Log\LoggerInterface;

/**
 * Class LoggerHelper
 */
final class LoggerHelper
{
    /**
     * creates a \Monolog\Logger instance
     *
     * @param bool $debug If true the debug logging mode will be enabled
     * @return LoggerInterface
     * @throws \Exception
     */
    public function create(?bool $debug = false) : LoggerInterface
    {
        $logger = new Logger('browscap');

        if ($debug) {
            $stream = new StreamHandler('php://output', Logger::DEBUG);
            $stream->setFormatter(
                new LineFormatter('[%datetime%] %channel%.%level_name%: %message% %extra%' . "\n")
            );

            /** @var callable $memoryProcessor */
            $memoryProcessor = new MemoryUsageProcessor(true);
            $logger->pushProcessor($memoryProcessor);

            /** @var callable $peakMemoryProcessor */
            $peakMemoryProcessor = new MemoryPeakUsageProcessor(true);
            $logger->pushProcessor($peakMemoryProcessor);
        } else {
            $stream = new StreamHandler('php://output', Logger::INFO);
            $stream->setFormatter(new LineFormatter('[%datetime%] %message% %extra%' . "\n"));

            /** @var callable $peakMemoryProcessor */
            $peakMemoryProcessor = new MemoryPeakUsageProcessor(true);
            $logger->pushProcessor($peakMemoryProcessor);
        }

        $logger->pushHandler($stream);
        $logger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Logger::NOTICE));

        ErrorHandler::register($logger);

        return $logger;
    }
}
