<?php

declare(strict_types=1);

namespace BrowscapPHPTest;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception as BrowscapException;
use BrowscapPHP\Exception\ErrorCachedVersionException;
use BrowscapPHP\Exception\ErrorReadingFileException;
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Exception\FileNameMissingException;
use BrowscapPHP\Exception\FileNotFoundException;
use BrowscapPHP\Exception\NoCachedVersionException;
use BrowscapPHP\Exception\NoNewVersionException;
use BrowscapPHP\Helper\IniLoaderInterface;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use MatthiasMullie\Scrapbook\Adapters\MemoryStore;
use MatthiasMullie\Scrapbook\Psr16\SimpleCache;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;
use ReflectionException;

use const DIRECTORY_SEPARATOR;

/** @covers \BrowscapPHP\BrowscapUpdater */
final class BrowscapUpdaterTest extends TestCase
{
    private const STORAGE_DIR = 'storage';

    private BrowscapUpdater $object;

    /** @throws void */
    protected function setUp(): void
    {
        $cache = $this->createMock(CacheInterface::class);

        $logger = $this->createMock(LoggerInterface::class);

        $this->object = new BrowscapUpdater($cache, $logger);
    }

    /**
     * @throws FileNameMissingException
     * @throws FileNotFoundException
     * @throws ErrorReadingFileException
     */
    public function testConvertEmptyFile(): void
    {
        $this->expectException(BrowscapException\FileNameMissingException::class);
        $this->expectExceptionMessage('the file name can not be empty');
        $this->object->convertFile('');
    }

    /**
     * @throws FileNameMissingException
     * @throws FileNotFoundException
     * @throws ErrorReadingFileException
     */
    public function testConvertNotReadableFile(): void
    {
        $this->expectException(BrowscapException\FileNotFoundException::class);
        $this->expectExceptionMessage('it was not possible to read the local file /this/file/does/not/exist');
        $this->object->convertFile('/this/file/does/not/exist');
    }

    /**
     * @throws FileNameMissingException
     * @throws FileNotFoundException
     * @throws ReflectionException
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
        $structure = ['test.ini' => $content];

        vfsStream::setup(self::STORAGE_DIR, null, $structure);

        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new SimpleCache(
            new MemoryStore(),
        );
        $cache   = new BrowscapCache($adapter, $logger);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $this->object->convertFile(vfsStream::url(self::STORAGE_DIR . DIRECTORY_SEPARATOR . 'test.ini'));

        self::assertSame(5031, $cache->getVersion());
    }

    /** @throws ReflectionException */
    public function testConvertString(): void
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

        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new SimpleCache(
            new MemoryStore(),
        );
        $cache   = new BrowscapCache($adapter, $logger);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $this->object->convertString($content);

        self::assertSame(5031, $cache->getVersion());
    }

    /**
     * @throws \BrowscapPHP\Helper\Exception
     * @throws FetcherException
     * @throws ErrorCachedVersionException
     * @throws ReflectionException
     */
    public function testFetchFail(): void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::exactly(2))->method('getStatusCode')->willReturn(500);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('client');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                6000,
            ],
        ];

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $this->expectException(BrowscapException\FetcherException::class);
        $this->expectExceptionMessage(
            'an error occured while fetching version data from URI http://browscap.org/version-number: StatusCode was 500',
        );
        $this->object->fetch(IniLoaderInterface::PHP_INI);
    }

    /**
     * @throws \BrowscapPHP\Helper\Exception
     * @throws FetcherException
     * @throws ErrorCachedVersionException
     * @throws ReflectionException
     */
    public function testFetchOK(): void
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

        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willReturn($content);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('client');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                null,
            ],
        ];

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $file = 'resources/test.ini';

        $this->object->fetch($file);

        self::assertStringEqualsFile($file, $content);
    }

    /**
     * @throws \BrowscapPHP\Helper\Exception
     * @throws FetcherException
     * @throws ErrorCachedVersionException
     * @throws ReflectionException
     */
    public function testFetchSanitizeOK(): void
    {
        $content = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031\'?><?php
Released=Mon, 30 Jun 2014 17:55:58 +0200\'?><?= exit(\'test\'); ?>
Format=ASP\'?><% exit(\'test\'); %>
Type=\'?><?php exit(\'\'); ?>

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

        $expected = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;; Browscap Version

[GJK_Browscap_Version]
Version=5031\'php
Released=Mon, 30 Jun 2014 17:55:58 +0200\'
Format=ASP\'
Type=\'

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

        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willReturn($content);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('client');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                null,
            ],
        ];

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $this->object->fetch(IniLoaderInterface::PHP_INI);

        self::assertStringEqualsFile(IniLoaderInterface::PHP_INI, $expected);
    }

    /**
     * @throws \BrowscapPHP\Helper\Exception
     * @throws FetcherException
     * @throws ErrorCachedVersionException
     * @throws ReflectionException
     */
    public function testUpdateFailException(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willReturn(false);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('client');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                null,
            ],
        ];

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $this->expectException(BrowscapException\FetcherException::class);
        $this->expectExceptionMessage('Could not fetch HTTP resource "http://browscap.org/stream?q=PHP_BrowscapINI":');
        $this->object->update();
    }

    /**
     * @throws \BrowscapPHP\Helper\Exception
     * @throws FetcherException
     * @throws ErrorCachedVersionException
     * @throws ReflectionException
     */
    public function testUpdateOk(): void
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

        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willReturn($content);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('client');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                null,
            ],
        ];

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::exactly(4355))->method('setItem');

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $this->object->update();
    }

    /**
     * @throws NoNewVersionException
     * @throws NoCachedVersionException
     * @throws FetcherException
     * @throws ErrorCachedVersionException
     * @throws ReflectionException
     */
    public function testCheckUpdateWithCacheFail(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::never())->method('getContents');

        $response = $this->createMock(Response::class);
        $response->expects(self::never())->method('getStatusCode');
        $response->expects(self::never())->method('getBody');

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('request');

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('client');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                null,
            ],
        ];

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $this->expectException(BrowscapException\NoCachedVersionException::class);
        $this->expectExceptionMessage(
            'there is no cached version available, please update from remote',
        );

        $this->object->checkUpdate();
    }

    /**
     * @throws NoNewVersionException
     * @throws NoCachedVersionException
     * @throws FetcherException
     * @throws ErrorCachedVersionException
     * @throws ReflectionException
     */
    public function testCheckUpdateWithException(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willThrowException(new Exception());

        $response = $this->createMock(Response::class);
        $response->expects(self::exactly(2))->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('client');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                6000,
            ],
        ];

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $this->expectException(BrowscapException\FetcherException::class);
        $this->expectExceptionMessage(
            'an error occured while fetching version data from URI http://browscap.org/version-number: StatusCode was 200',
        );
        $this->object->checkUpdate();
    }

    /**
     * @throws NoNewVersionException
     * @throws NoCachedVersionException
     * @throws FetcherException
     * @throws ErrorCachedVersionException
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testCheckUpdateWithoutNewerVersion(): void
    {
        $version = 6000;

        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willReturn($version);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('client');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                $version,
            ],
        ];

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $this->expectException(BrowscapException\NoNewVersionException::class);
        $this->expectExceptionMessage(
            'there is no newer version available',
        );

        $this->object->checkUpdate();
    }

    /**
     * @throws NoNewVersionException
     * @throws NoCachedVersionException
     * @throws FetcherException
     * @throws ErrorCachedVersionException
     * @throws ReflectionException
     */
    public function testCheckUpdateWithNewerVersion(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willReturn(6001);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('client');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $client);

        $map = [
            [
                'browscap.time',
                false,
                null,
                null,
            ],
            [
                'browscap.version',
                false,
                null,
                6000,
            ],
        ];

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::any())->method('getItem')->willReturnMap($map);
        $cache->expects(self::any())->method('hasItem')->willReturn(true);
        $cache->expects(self::never())->method('setItem');

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        self::assertSame(6000, $this->object->checkUpdate());
    }
}
