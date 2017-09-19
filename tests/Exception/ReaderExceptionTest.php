<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Exception;

use BrowscapPHP\Exception\ReaderException;

/**
 * @covers \BrowscapPHP\Exception\ReaderException
 */
class ReaderExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testUserAgentParserError() : void
    {
        $exception = ReaderException::userAgentParserError('42');

        self::assertInstanceOf(ReaderException::class, $exception);
        self::assertSame(
            'Cannot extract user agent string from line "42"',
            $exception->getMessage()
        );
    }
}
