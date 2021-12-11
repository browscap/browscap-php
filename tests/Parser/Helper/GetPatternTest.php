<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Parser\Helper\GetPattern;
use Generator;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

use function serialize;

/**
 * @covers \BrowscapPHP\Parser\Helper\GetPattern
 */
final class GetPatternTest extends TestCase
{
    private GetPattern $object;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $map = [
            [
                'browscap.version',
                null,
                [
                    'content' => serialize(42),
                ],
            ],
            [
                'test.42',
                null,
                [
                    'content' => serialize('this is a test'),
                ],
            ],
        ];

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache
            ->expects(self::never())
            ->method('getItem')
            ->willReturnMap($map);

        $logger = $this->createMock(LoggerInterface::class);

        $this->object = new GetPattern($cache, $logger);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testGetPatterns(): void
    {
        $result = $this->object->getPatterns('Mozilla/5.0 (compatible; Ask Jeeves/Teoma*)');

        self::assertInstanceOf(Generator::class, $result);
    }
}
