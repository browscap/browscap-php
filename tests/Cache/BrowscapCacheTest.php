<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Cache;

use BrowscapPHP\Cache\BrowscapCache;
use Doctrine\Common\Cache\ArrayCache;
use Psr\Log\LoggerInterface;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;

final class BrowscapCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \Roave\DoctrineSimpleCache\Exception\CacheException
     * @throws \PHPUnit\Framework\Exception
     * @throws \InvalidArgumentException
     */
    public function testConstruct() : void
    {
        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter, $logger);

        static::assertInstanceOf(BrowscapCache::class, $cache);
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \Roave\DoctrineSimpleCache\Exception\CacheException
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testVersion() : void
    {
        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter, $logger);

        static::assertNull($cache->getVersion());

        $cache->setItem('browscap.version', 6012, false);
        static::assertSame(6012, $cache->getVersion());
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \Roave\DoctrineSimpleCache\Exception\CacheException
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testReleaseDate() : void
    {
        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter, $logger);

        static::assertNull($cache->getVersion());

        $cache->setItem('browscap.releaseDate', 'Thu, 04 Feb 2016 12:59:23 +0000', false);
        static::assertSame('Thu, 04 Feb 2016 12:59:23 +0000', $cache->getReleaseDate());
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \Roave\DoctrineSimpleCache\Exception\CacheException
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testType() : void
    {
        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter, $logger);

        static::assertNull($cache->getType());

        $cache->setItem('browscap.type', 'LITE', false);
        static::assertSame('LITE', $cache->getType());
    }
}
