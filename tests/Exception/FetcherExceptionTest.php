<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Exception;

use BrowscapPHP\Exception\FetcherException;

final class FetcherExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
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
