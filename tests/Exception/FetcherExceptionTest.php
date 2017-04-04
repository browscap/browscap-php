<?php
declare(strict_types=1);

namespace BrowscapPHPTest\Exception;

use BrowscapPHP\Exception\FetcherException;

/**
 * @covers \BrowscapPHP\Exception\FetcherException
 */
final class FetcherExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testHttpError() : void
    {
        $exception = FetcherException::httpError('http://example.org', 'Uri not reachable');

        self::assertInstanceOf(FetcherException::class, $exception);
        self::assertSame(
            'Could not fetch HTTP resource "http://example.org": Uri not reachable',
            $exception->getMessage()
        );
    }
}
