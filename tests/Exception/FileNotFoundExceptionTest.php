<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Exception;

use BrowscapPHP\Exception\FileNotFoundException;

final class FileNotFoundExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testFileNotFound() : void
    {
        $exception = FileNotFoundException::fileNotFound('test.txt');

        self::assertInstanceOf(FileNotFoundException::class, $exception);
        self::assertSame(
            'File "test.txt" does not exist',
            $exception->getMessage()
        );
    }
}
