<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Parser\Helper;

use BrowscapPHP\Parser\Helper\SubKey;

final class SubKeyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testGetPatternCacheSubkey() : void
    {
        static::assertSame('ab', SubKey::getPatternCacheSubkey('abcd'));
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testGetAllPatternCacheSubkeys() : void
    {
        $result = SubKey::getAllPatternCacheSubkeys();
        static::assertIsArray($result);
        static::assertCount(256, $result);
    }
}
