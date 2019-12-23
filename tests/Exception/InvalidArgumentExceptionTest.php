<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Exception;

use BrowscapPHP\Exception\InvalidArgumentException;

final class InvalidArgumentExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testOneOfCommandArguments() : void
    {
        $exception = InvalidArgumentException::oneOfCommandArguments('http://example.org', 'Uri not reachable');

        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertSame(
            'One of the command arguments "http://example.org", "Uri not reachable" is required',
            $exception->getMessage()
        );
    }
}
