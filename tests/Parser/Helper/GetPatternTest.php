<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Parser\Helper\GetPattern;
use Psr\Log\LoggerInterface;

final class GetPatternTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GetPattern
     */
    private $object;

    /**
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    protected function setUp() : void
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

        /** @var BrowscapCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache
            ->expects(static::never())
            ->method('getItem')
            ->willReturnMap($map);

        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->object = new GetPattern($cache, $logger);
    }

    /**
     * @throws \PHPUnit\Framework\Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testGetPatterns() : void
    {
        $result = $this->object->getPatterns('Mozilla/5.0 (compatible; Ask Jeeves/Teoma*)');

        static::assertInstanceOf(\Generator::class, $result);
    }
}
