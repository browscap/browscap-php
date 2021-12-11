<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Parser\Helper;

use BrowscapPHP\Parser\Helper\SubKey;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * @covers \BrowscapPHP\Parser\Helper\SubKey
 */
final class SubKeyTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testGetPatternCacheSubkey(): void
    {
        self::assertSame('ab', SubKey::getPatternCacheSubkey('abcd'));
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testGetAllPatternCacheSubkeys(): void
    {
        $result = SubKey::getAllPatternCacheSubkeys();
        self::assertIsArray($result);
        self::assertCount(256, $result);
    }
}
