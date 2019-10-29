<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Helper\Converter;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception\FileNotFoundException;
use BrowscapPHP\Helper\Converter;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;

final class ConverterTest extends \PHPUnit\Framework\TestCase
{
    private const STORAGE_DIR = 'storage';

    /**
     * @var \BrowscapPHP\Helper\Converter
     */
    private $object;

    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $root;

    /**
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    protected function setUp() : void
    {
        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::never())
            ->method('info')
            ->willReturn(false);

        /** @var BrowscapCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(static::any())
            ->method('setItem')
            ->willReturn(true);

        $this->object = new Converter($logger, $cache);
    }

    /**
     * @throws \BrowscapPHP\Exception\FileNotFoundException
     * @throws \BrowscapPHP\Exception\ErrorReadingFileException
     */
    public function testConvertMissingFile() : void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('testFile');
        $this->object->convertFile('testFile');
    }

    /**
     * @throws \BrowscapPHP\Exception\FileNotFoundException
     * @throws \BrowscapPHP\Exception\ErrorReadingFileException
     */
    public function testConvertFile() : void
    {
        $content = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

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
            self::STORAGE_DIR => [
                'test.ini' => $content,
            ],
        ];

        $this->root = vfsStream::setup(self::STORAGE_DIR, null, $structure);

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File "vfs://storage/test.ini" does not exist');
        $this->object->convertFile(vfsStream::url(self::STORAGE_DIR . \DIRECTORY_SEPARATOR . 'test.ini'));
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testGetIniVersion() : void
    {
        $content = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031
Released=Mon, 30 Jun 2014 17:55:58 +0200
Format=ASP
Type=';

        static::assertSame(5031, $this->object->getIniVersion($content));
    }
}
