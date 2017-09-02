<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Util\LogFile;

use BrowscapPHP\Util\Logfile\ReaderCollection;
use BrowscapPHP\Util\Logfile\ReaderFactory;

/**
 * @covers \BrowscapPHP\Util\Logfile\ReaderFactory
 */
final class ReaderFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testFactoryCreatesCollection() : void
    {
        self::assertInstanceOf(ReaderCollection::class, ReaderFactory::factory());
    }
}
