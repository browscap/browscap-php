<?php

declare(strict_types=1);

namespace BrowscapPHPTest\Helper\Converter;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception\ErrorReadingFileException;
use BrowscapPHP\Exception\FileNotFoundException;
use BrowscapPHP\Helper\Converter;
use BrowscapPHP\Helper\Filesystem;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionException;
use ReflectionProperty;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

use const DIRECTORY_SEPARATOR;

/**
 * @covers \BrowscapPHP\Helper\Converter
 */
final class ConverterTest extends TestCase
{
    private const STORAGE_DIR = 'storage';

    private Converter $object;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())
            ->method('info');

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::any())
            ->method('setItem')
            ->willReturn(true);

        $this->object = new Converter($logger, $cache);
    }

    /**
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testSetGetFilesystem(): void
    {
        $file = $this->createMock(Filesystem::class);

        $this->object->setFilesystem($file);

        $property = new ReflectionProperty($this->object, 'filessystem');
        $property->setAccessible(true);

        self::assertSame($file, $property->getValue($this->object));
    }

    /**
     * @throws FileNotFoundException
     * @throws Exception
     * @throws ErrorReadingFileException
     */
    public function testConvertMissingFile(): void
    {
        $file = $this->createMock(Filesystem::class);
        $file->expects(self::once())
            ->method('exists')
            ->willReturn(false);

        $this->object->setFilesystem($file);

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('testFile');
        $this->object->convertFile('testFile');
    }

    /**
     * @throws FileNotFoundException
     * @throws Exception
     * @throws ErrorReadingFileException
     */
    public function testConvertFile(): void
    {
        $content   = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031
Released=Mon, 30 Jun 2014 17:55:58 +0200
Format=ASP
Type=

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; DefaultProperties

[DefaultProperties]

Comment=DefaultProperties
Browser=DefaultProperties
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=false
Beta=false
Win16=false
Win32=false
Win64=false
Frames=false
IFrames=false
Tables=false
Cookies=false
BackgroundSounds=false
JavaScript=false
VBScript=false
JavaApplets=false
ActiveXControls=false
isMobileDevice=false
isTablet=false
isSyndicationReader=false
Crawler=false
CssVersion=0
AolVersion=0

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Ask

[Ask]

Parent=DefaultProperties
Comment=Ask
Browser=Ask
Frames=1
IFrames=1
Tables=1
Crawler=1
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
CssVersion=0
AolVersion=0

[Mozilla/?.0 (compatible; Ask Jeeves/Teoma*)]

Parent=Ask
Browser=Teoma
Comment=Ask
Version=0.0
MajorVer=0
MinorVer=0
Platform=unknown
Platform_Version=unknown
Alpha=
Beta=
Win16=
Win32=
Win64=
Frames=1
IFrames=1
Tables=1
Cookies=
BackgroundSounds=
JavaScript=
VBScript=
JavaApplets=
ActiveXControls=
isMobileDevice=
isTablet=
isSyndicationReader=
Crawler=1
CssVersion=0
AolVersion=0
';
        $structure = [
            self::STORAGE_DIR => ['test.ini' => $content],
        ];

        vfsStream::setup(self::STORAGE_DIR, null, $structure);

        $file = $this->createMock(Filesystem::class);
        $file->expects(self::once())
            ->method('exists')
            ->willReturn(false);

        $this->object->setFilesystem($file);

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File "vfs://storage/test.ini" does not exist');
        $this->object->convertFile(vfsStream::url(self::STORAGE_DIR . DIRECTORY_SEPARATOR . 'test.ini'));
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testGetIniVersion(): void
    {
        $file = $this->createMock(Filesystem::class);
        $file->expects(self::never())
            ->method('exists')
            ->willReturn(false);

        $this->object->setFilesystem($file);

        $content = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031
Released=Mon, 30 Jun 2014 17:55:58 +0200
Format=ASP
Type=';

        self::assertSame(5031, $this->object->getIniVersion($content));
    }
}
