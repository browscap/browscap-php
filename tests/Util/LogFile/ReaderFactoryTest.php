<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Util\LogFile;

use BrowscapPHP\Util\Logfile\ReaderFactory;
use BrowscapPHP\Util\Logfile\ReaderCollection;

/**
 * @covers \BrowscapPHP\Util\Logfile\ReaderFactory
 */
final class ReaderFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryCreatesCollection() : void
    {
        self::assertInstanceOf(ReaderCollection::class, ReaderFactory::factory());
    }
}
