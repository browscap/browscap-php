<?php
declare(strict_types = 1);

namespace BrowscapPHPTest;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception as BrowscapException;
use BrowscapPHP\Helper\IniLoaderInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use org\bovigo\vfs\vfsStream;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * @covers \BrowscapPHP\BrowscapUpdater
 */
final class BrowscapUpdaterTest extends \PHPUnit_Framework_TestCase
{
    const STORAGE_DIR = 'storage';

    /**
     * @var BrowscapUpdater
     */
    private $object;

    public function setUp() : void
    {
        /** @var CacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(CacheInterface::class);

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->object = new BrowscapUpdater($cache, $logger);
    }

    public function testSetConnectTimeout() : void
    {
        $timeout = random_int(1, 100);

        $this->object->setConnectTimeout($timeout);

        self::assertAttributeSame($timeout, 'connectTimeout', $this->object);
    }

    public function testGetClient() : void
    {
        self::assertInstanceOf(ClientInterface::class, $this->object->getClient());
    }

    public function testSetGetClient() : void
    {
        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(ClientInterface::class);

        $this->object->setClient($client);
        self::assertSame($client, $this->object->getClient());
    }

    public function testConvertEmptyFile() : void
    {
        $this->expectException(BrowscapException::class);
        $this->expectExceptionMessage('the file name can not be empty');
        $this->object->convertFile('');
    }

    public function testConvertNotReadableFile() : void
    {
        $this->expectException(BrowscapException::class);
        $this->expectExceptionMessage('it was not possible to read the local file /this/file/does/not/exist');
        $this->object->convertFile('/this/file/does/not/exist');
    }

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
            'test.ini' => $content,
        ];

        vfsStream::setup(self::STORAGE_DIR, null, $structure);

        $cache = new Memory();
        $this->object->setCache($cache);

        $this->object->convertFile(vfsStream::url(self::STORAGE_DIR . DIRECTORY_SEPARATOR . 'test.ini'));

        self::assertSame(5031, $this->object->getCache()->getVersion());
    }

    public function testConvertString() : void
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

        $cache = new Memory();
        $this->object->setCache($cache);

        $this->object->convertString($content);

        self::assertSame(5031, $this->object->getCache()->getVersion());
    }

    public function testFetchFail() : void
    {
        $response = $this->createMock(Response::class);
        $response->expects(self::exactly(2))->method('getStatusCode')->will(self::returnValue(500));

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->will(self::returnValue($response));

        $this->object->setClient($client);

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

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $this->object->setCache($cache);

        $this->expectException(BrowscapException\FetcherException::class);
        $this->expectExceptionMessage(
            'an error occured while fetching version data from URI http://browscap.org/version-number: StatusCode was 500'
        );
        $this->object->fetch(IniLoaderInterface::PHP_INI);
    }

    public function testFetchOK() : void
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

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $this->object->setClient($client);

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

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $this->object->setCache($cache);

        $file = 'resources/test.ini';

        $this->object->fetch($file);

        self::assertStringEqualsFile($file, $content);
    }

    public function testFetchSanitizeOK() : void
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

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $this->object->setClient($client);

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

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $this->object->setCache($cache);

        $this->object->fetch(IniLoaderInterface::PHP_INI);

        self::assertStringEqualsFile(IniLoaderInterface::PHP_INI, $expected);
    }

    public function testUpdateFailException() : void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willReturn(false);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $this->object->setClient($client);

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

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $this->object->setCache($cache);

        $this->expectException(BrowscapException\FetcherException::class);
        $this->expectExceptionMessage('Could not fetch HTTP resource "http://browscap.org/stream?q=PHP_BrowscapINI":');
        $this->object->update();
    }

    public function testUpdateOk() : void
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

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $this->object->setClient($client);

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

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::exactly(4355))->method('setItem');

        $this->object->setCache($cache);

        $this->object->update();
    }

    public function testCheckUpdateWithCacheFail() : void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::never())->method('getContents');

        $response = $this->createMock(Response::class);
        $response->expects(self::never())->method('getStatusCode');
        $response->expects(self::never())->method('getBody');

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('request');

        $this->object->setClient($client);

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

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $this->object->setCache($cache);

        $this->expectException(BrowscapException\NoCachedVersionException::class);
        $this->expectExceptionMessage(
            'there is no cached version available, please update from remote'
        );

        $this->object->checkUpdate();
    }

    public function testCheckUpdateWithException() : void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willThrowException(new \Exception());

        $response = $this->createMock(Response::class);
        $response->expects(self::exactly(2))->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $this->object->setClient($client);

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

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $this->object->setCache($cache);

        $this->expectException(BrowscapException\FetcherException::class);
        $this->expectExceptionMessage(
            'an error occured while fetching version data from URI http://browscap.org/version-number: StatusCode was 200'
        );
        $this->object->checkUpdate();
    }

    public function testCheckUpdateWithoutNewerVersion() : void
    {
        $version = 6000;

        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willReturn($version);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $this->object->setClient($client);

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

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getItem')->willReturnMap($map);
        $cache->expects(self::never())->method('setItem');

        $this->object->setCache($cache);

        self::assertNull($this->object->checkUpdate());
    }

    public function testCheckUpdateWithNewerVersion() : void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::once())->method('getContents')->willReturn(6001);

        $response = $this->createMock(Response::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getBody')->willReturn($body);

        /** @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('request')->willReturn($response);

        $this->object->setClient($client);

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

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::any())->method('getItem')->willReturnMap($map);
        $cache->expects(self::any())->method('hasItem')->willReturn(true);
        $cache->expects(self::never())->method('setItem');

        $this->object->setCache($cache);

        self::assertSame(6000, $this->object->checkUpdate());
    }
}
