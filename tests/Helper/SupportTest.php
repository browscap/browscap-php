<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\Support;

/**
 * @covers \BrowscapPHP\Helper\Support
 */
final class SupportTest extends \PHPUnit_Framework_TestCase
{
    public function testUserAgentIsTakenFromServerArray() : void
    {
        $source = ['HTTP_USER_AGENT' => 'testUA'];
        $object = new Support($source);

        self::assertSame('testUA', $object->getUserAgent());
    }

    public function testThatAnEmptyUserAgentIsReturnedWithoutSource() : void
    {
        $object = new Support();

        self::assertSame('', $object->getUserAgent());
    }
}
