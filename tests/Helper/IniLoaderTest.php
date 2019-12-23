<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\Exception;
use BrowscapPHP\Helper\IniLoader;
use BrowscapPHP\Helper\IniLoaderInterface;

final class IniLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IniLoader
     */
    private $object;

    protected function setUp() : void
    {
        $this->object = new IniLoader();
    }

    /**
     * @throws \BrowscapPHP\Helper\Exception
     */
    public function testSetMissingRemoteFilename() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('the filename can not be empty');
        $this->object->setRemoteFilename('');
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     * @throws \BrowscapPHP\Helper\Exception
     */
    public function testGetRemoteIniUrl() : void
    {
        $this->object->setRemoteFilename(IniLoaderInterface::PHP_INI_LITE);
        self::assertSame('http://browscap.org/stream?q=Lite_PHP_BrowscapINI', $this->object->getRemoteIniUrl());

        $this->object->setRemoteFilename(IniLoaderInterface::PHP_INI);
        self::assertSame('http://browscap.org/stream?q=PHP_BrowscapINI', $this->object->getRemoteIniUrl());

        $this->object->setRemoteFilename(IniLoaderInterface::PHP_INI_FULL);
        self::assertSame('http://browscap.org/stream?q=Full_PHP_BrowscapINI', $this->object->getRemoteIniUrl());
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testGetRemoteVerUrl() : void
    {
        self::assertSame('http://browscap.org/version', $this->object->getRemoteTimeUrl());
    }
}
