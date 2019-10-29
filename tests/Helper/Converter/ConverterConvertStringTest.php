<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Helper\Converter;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Helper\Converter;
use Psr\Log\LoggerInterface;

final class ConverterConvertStringTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \BrowscapPHP\Helper\Converter
     */
    private $object;

    /**
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    protected function setUp() : void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::exactly(4))
            ->method('info')
            ->willReturn(false);
        $logger->expects(static::never())
            ->method('error')
            ->willReturn(false);

        /** @var BrowscapCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(static::any())
            ->method('setItem')
            ->willReturn(true);

        $this->object = new Converter($logger, $cache);
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

        $this->object->convertString($content);
    }

    public function testConvertStringWithoutPatternFound() : void
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
';

        $this->object->convertString($content);
    }
}
