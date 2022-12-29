<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Cache;

use BrowscapPHP\Cache\BrowscapCache;
use MatthiasMullie\Scrapbook\Adapters\MemoryStore;
use MatthiasMullie\Scrapbook\Psr16\SimpleCache;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/** @covers \BrowscapPHP\Cache\BrowscapCache */
final class BrowscapCacheTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testConstruct(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new SimpleCache(
            new MemoryStore(),
        );
        $cache   = new BrowscapCache($adapter, $logger);

        self::assertInstanceOf(BrowscapCache::class, $cache);
    }

    /** @throws \Psr\SimpleCache\InvalidArgumentException */
    public function testVersion(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new SimpleCache(
            new MemoryStore(),
        );
        $cache   = new BrowscapCache($adapter, $logger);

        self::assertNull($cache->getVersion());

        $cache->setItem('browscap.version', 6012, false);
        self::assertSame(6012, $cache->getVersion());
    }

    /** @throws \Psr\SimpleCache\InvalidArgumentException */
    public function testReleaseDate(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new SimpleCache(
            new MemoryStore(),
        );
        $cache   = new BrowscapCache($adapter, $logger);

        self::assertNull($cache->getVersion());

        $cache->setItem('browscap.releaseDate', 'Thu, 04 Feb 2016 12:59:23 +0000', false);
        self::assertSame('Thu, 04 Feb 2016 12:59:23 +0000', $cache->getReleaseDate());
    }

    /** @throws \Psr\SimpleCache\InvalidArgumentException */
    public function testType(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new SimpleCache(
            new MemoryStore(),
        );
        $cache   = new BrowscapCache($adapter, $logger);

        self::assertNull($cache->getType());

        $cache->setItem('browscap.type', 'LITE', false);
        self::assertSame('LITE', $cache->getType());
    }
}
