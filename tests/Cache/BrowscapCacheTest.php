<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Cache\BrowscapCache;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit_Framework_TestCase;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;

/**
 * @covers \BrowscapPHP\Cache\BrowscapCache
 */
final class BrowscapCacheTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct() : void
    {
        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter);

        self::assertInstanceOf(BrowscapCache::class, $cache);
    }

    public function testVersion() : void
    {
        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter);

        self::assertNull($cache->getVersion());

        $cache->setItem('browscap.version', 6012, false);
        self::assertEquals(6012, $cache->getVersion());
    }

    public function testReleaseDate() : void
    {
        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter);

        self::assertNull($cache->getVersion());

        $cache->setItem('browscap.releaseDate', 'Thu, 04 Feb 2016 12:59:23 +0000', false);
        self::assertEquals('Thu, 04 Feb 2016 12:59:23 +0000', $cache->getReleaseDate());
    }

    public function testType() : void
    {
        $memoryCache = new ArrayCache();
        $adapter = new SimpleCacheAdapter($memoryCache);
        $cache = new BrowscapCache($adapter);

        self::assertNull($cache->getType());

        $cache->setItem('browscap.type', 'LITE', false);
        self::assertEquals('LITE', $cache->getType());
    }
}
