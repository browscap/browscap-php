<?php
declare(strict_types=1);

namespace BrowscapPHPTest\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Parser\Helper\GetPattern;
use Psr\Log\LoggerInterface;

/**
 * @covers \BrowscapPHP\Parser\Helper\GetPattern
 */
final class GetPatternTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GetPattern
     */
    private $object;

    public function setUp() : void
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

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache
            ->expects(self::never())
            ->method('getItem')
            ->will(self::returnValueMap($map));

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->object = new GetPattern($cache, $logger);
    }

    public function testGetPatterns() : void
    {
        $result = $this->object->getPatterns('Mozilla/5.0 (compatible; Ask Jeeves/Teoma*)');

        self::assertInstanceOf(\Generator::class, $result);
    }
}
