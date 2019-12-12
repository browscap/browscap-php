<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Parser\Helper;

use BrowscapPHP\Parser\Helper\SubKey;

/**
 * @covers \BrowscapPHP\Parser\Helper\SubKey
 */
final class SubKeyTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPatternCacheSubkey() : void
    {
        self::assertSame('ab', SubKey::getPatternCacheSubkey('abcd'));
    }

    public function testGetAllPatternCacheSubkeys() : void
    {
        $result = SubKey::getAllPatternCacheSubkeys();
        self::assertIsArray($result);
        self::assertCount(256, $result);
    }
}
