<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\LoggerHelper;
use Monolog\Logger;

/**
 * @covers \BrowscapPHP\Helper\LoggerHelper
 */
class LoggerHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate() : void
    {
        self::assertInstanceOf(Logger::class, LoggerHelper::createDefaultLogger());
    }

    public function testCreateInDebugMode() : void
    {
        self::assertInstanceOf(Logger::class, LoggerHelper::createDefaultLogger(true));
    }
}
