<?php
declare(strict_types=1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\Exception;
use BrowscapPHP\Helper\IniLoader;

/**
 * @covers \BrowscapPHP\Helper\IniLoader
 */
final class IniLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IniLoader
     */
    private $object;

    public function setUp() : void
    {
        $this->object = new IniLoader();
    }

    public function testSetMissingRemoteFilename() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('the filename can not be empty');
        self::assertSame($this->object, $this->object->setRemoteFilename(''));
    }

    public function testSetRemoteFilename() : void
    {
        self::assertSame($this->object, $this->object->setRemoteFilename('testFile'));
    }

    public function testGetRemoteIniUrl() : void
    {
        $this->object->setRemoteFilename(IniLoader::PHP_INI_LITE);
        self::assertSame('http://browscap.org/stream?q=Lite_PHP_BrowscapINI', $this->object->getRemoteIniUrl());

        $this->object->setRemoteFilename(IniLoader::PHP_INI);
        self::assertSame('http://browscap.org/stream?q=PHP_BrowscapINI', $this->object->getRemoteIniUrl());

        $this->object->setRemoteFilename(IniLoader::PHP_INI_FULL);
        self::assertSame('http://browscap.org/stream?q=Full_PHP_BrowscapINI', $this->object->getRemoteIniUrl());
    }

    public function testGetRemoteVerUrl() : void
    {
        self::assertSame('http://browscap.org/version', $this->object->getRemoteTimeUrl());
    }
}
