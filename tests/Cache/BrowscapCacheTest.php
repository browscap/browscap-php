<?php

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Cache\BrowscapCache;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\Cache\Simple\NullCache;

class BrowscapCacheTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $adapter = new NullCache();
        $cache   = new BrowscapCache($adapter);

        $this->assertInstanceOf('BrowscapPHP\Cache\BrowscapCache', $cache);
    }

    public function testVersion()
    {
        $adapter = new ArrayCache();
        $cache   = new BrowscapCache($adapter);

        $this->assertNull($cache->getVersion());

        $cache->setItem('browscap.version', 6012, false);
        $this->assertEquals(6012, $cache->getVersion());
    }

    public function testReleaseDate()
    {
        $adapter = new ArrayCache();
        $cache   = new BrowscapCache($adapter);

        $this->assertNull($cache->getVersion());

        $cache->setItem('browscap.releaseDate', 'Thu, 04 Feb 2016 12:59:23 +0000', false);
        $this->assertEquals('Thu, 04 Feb 2016 12:59:23 +0000', $cache->getReleaseDate());
    }

    public function testType()
    {
        $adapter = new ArrayCache();
        $cache   = new BrowscapCache($adapter);

        $this->assertNull($cache->getType());

        $cache->setItem('browscap.type', 'LITE', false);
        $this->assertEquals('LITE', $cache->getType());
    }
}
