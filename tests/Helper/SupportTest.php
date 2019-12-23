<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\Support;

final class SupportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testUserAgentIsTakenFromServerArray() : void
    {
        $source = ['HTTP_USER_AGENT' => 'testUA'];
        $object = new Support($source);

        self::assertSame('testUA', $object->getUserAgent());
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testThatAnEmptyUserAgentIsReturnedWithoutSource() : void
    {
        $object = new Support();

        self::assertSame('', $object->getUserAgent());
    }
}
