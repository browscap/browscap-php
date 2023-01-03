<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\Exception;
use BrowscapPHP\Helper\IniLoader;
use BrowscapPHP\Helper\IniLoaderInterface;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/** @covers \BrowscapPHP\Helper\IniLoader */
final class IniLoaderTest extends TestCase
{
    private IniLoader $object;

    /** @throws void */
    protected function setUp(): void
    {
        $this->object = new IniLoader();
    }

    /** @throws Exception */
    public function testSetMissingRemoteFilename(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('the filename can not be empty');
        $this->object->setRemoteFilename('');
    }

    /** @throws Exception */
    public function testGetRemoteIniUrl(): void
    {
        $this->object->setRemoteFilename(IniLoaderInterface::PHP_INI_LITE);
        self::assertSame('http://browscap.org/stream?q=Lite_PHP_BrowscapINI', $this->object->getRemoteIniUrl());

        $this->object->setRemoteFilename(IniLoaderInterface::PHP_INI);
        self::assertSame('http://browscap.org/stream?q=PHP_BrowscapINI', $this->object->getRemoteIniUrl());

        $this->object->setRemoteFilename(IniLoaderInterface::PHP_INI_FULL);
        self::assertSame('http://browscap.org/stream?q=Full_PHP_BrowscapINI', $this->object->getRemoteIniUrl());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testGetRemoteVerUrl(): void
    {
        self::assertSame('http://browscap.org/version', $this->object->getRemoteTimeUrl());
    }
}
