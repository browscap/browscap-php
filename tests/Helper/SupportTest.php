<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\Support;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * @covers \BrowscapPHP\Helper\Support
 */
final class SupportTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testUserAgentIsTakenFromServerArray(): void
    {
        $source = ['HTTP_USER_AGENT' => 'testUA'];
        $object = new Support($source);

        self::assertSame('testUA', $object->getUserAgent());
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testThatAnEmptyUserAgentIsReturnedWithoutSource(): void
    {
        $object = new Support();

        self::assertSame('', $object->getUserAgent());
    }
}
