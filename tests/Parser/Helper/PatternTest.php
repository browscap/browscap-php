<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Parser\Helper;

use BrowscapPHP\Parser\Helper\Pattern;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

use function strtolower;

/**
 * @covers \BrowscapPHP\Parser\Helper\Pattern
 */
final class PatternTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @throws Exception
     *
     * @group pattern
     */
    public function testGetPatternStartWithoutVariants(): void
    {
        $pattern = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.68 Safari/537.36';
        self::assertSame('aaa556aeec36ac3edfe2f5deea5f1d28', Pattern::getHashForPattern(strtolower($pattern), false)[0]);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     *
     * @group pattern
     */
    public function testGetPatternStartWithVariants(): void
    {
        $pattern  = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.68 Safari/537.36';
        $expected = [
            0 => 'aaa556aeec36ac3edfe2f5deea5f1d28',
            1 => '31d050fd7a4ea6c972063ef30d18991a',
            2 => 'dbeb1c32b66fd7717de583d999f89ec3',
            3 => '13e6ce11d0a70e2a5a3df41bf11d493e',
            4 => '3a4a9ff7cf86e273442bad1305f3d1fd',
            5 => 'b70924c16a59b9cc2de329464b64118e',
            6 => '89364cb625249b3d478bace02699e05d',
            7 => '27c9d5187cd283f8d160ec1ed2b5ac89',
            8 => '6f8f57715090da2632453988d9a1501b',
            9 => 'd41d8cd98f00b204e9800998ecf8427e',
        ];

        self::assertSame($expected, Pattern::getHashForPattern(strtolower($pattern), true));
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     *
     * @group pattern
     */
    public function testGetPatternLength(): void
    {
        self::assertSame(4, Pattern::getPatternLength('abcd'));
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     *
     * @group pattern
     */
    public function testGetHashForParts(): void
    {
        self::assertSame(
            '529f1ddb64ea27d5cc6fc8ce8048d9e7',
            Pattern::getHashForParts('mozilla/5.0 (*linux i686*rv:0.9*) gecko*')
        );
    }
}
