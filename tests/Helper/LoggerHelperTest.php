<?php
declare(strict_types=1);

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
        $helper = new LoggerHelper();
        self::assertInstanceOf(Logger::class, $helper->create());
    }

    public function testCreateInDebugMode() : void
    {
        $helper = new LoggerHelper();
        self::assertInstanceOf(Logger::class, $helper->create(true));
    }
}
