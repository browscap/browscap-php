<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Cache;

use BrowscapPHP\Cache\BrowscapCache;
use Doctrine\Common\Cache\ArrayCache;
use Psr\Log\LoggerInterface;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;

/**
 * @covers \BrowscapPHP\Cache\BrowscapCache
 */
final class BrowscapCacheTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct() : void
    {
        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter, $logger);

        self::assertInstanceOf(BrowscapCache::class, $cache);
    }

    public function testVersion() : void
    {
        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter, $logger);

        self::assertNull($cache->getVersion());

        $cache->setItem('browscap.version', 6012, false);
        self::assertSame(6012, $cache->getVersion());
    }

    public function testReleaseDate() : void
    {
        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter, $logger);

        self::assertNull($cache->getVersion());

        $cache->setItem('browscap.releaseDate', 'Thu, 04 Feb 2016 12:59:23 +0000', false);
        self::assertSame('Thu, 04 Feb 2016 12:59:23 +0000', $cache->getReleaseDate());
    }

    public function testType() : void
    {
        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter, $logger);

        self::assertNull($cache->getType());

        $cache->setItem('browscap.type', 'LITE', false);
        self::assertSame('LITE', $cache->getType());
    }
}
