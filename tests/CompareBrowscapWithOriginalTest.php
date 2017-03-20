<?php

namespace BrowscapPHPTest;

use BrowscapPHP\Browscap;
use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Cache\BrowscapCache;
use WurflCache\Adapter\Memory;

/**
 * Compares get_browser results for all matches in browscap.ini with results from Browscap class.
 * Also compares the execution times.
 *
 * @group compare
 */
class CompareBrowscapWithOriginalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BrowscapPHP\Browscap
     */
    private static $object = null;

    /**
     * @var array
     */
    private $properties = [
        'browser_name_regex' => null,
        'browser_name_pattern' => null,
        'Parent' => null,
        'Comment' => 'Default Browser',
        'Browser' => 'Default Browser',
        'Browser_Type' => 'unknown',
        'Browser_Bits' => '0',
        'Browser_Maker' => 'unknown',
        'Browser_Modus' => 'unknown',
        'Version' => '0.0',
        'MajorVer' => '0',
        'MinorVer' => '0',
        'Platform' => 'unknown',
        'Platform_Version' => 'unknown',
        'Platform_Description' => 'unknown',
        'Platform_Bits' => '0',
        'Platform_Maker' => 'unknown',
        'Alpha' => 'false',
        'Beta' => 'false',
        'Win16' => 'false',
        'Win32' => 'false',
        'Win64' => 'false',
        'Frames' => 'false',
        'IFrames' => 'false',
        'Tables' => 'false',
        'Cookies' => 'false',
        'BackgroundSounds' => 'false',
        'JavaScript' => 'false',
        'VBScript' => 'false',
        'JavaApplets' => 'false',
        'ActiveXControls' => 'false',
        'isMobileDevice' => 'false',
        'isTablet' => 'false',
        'isSyndicationReader' => 'false',
        'Crawler' => 'false',
        'CssVersion' => '0',
        'AolVersion' => '0',
        'IsFake' => 'false',
        'IsAnonymized' => 'false',
        'IsModified' => 'false',
        'Device_Name' => 'unknown',
        'Device_Maker' => 'unknown',
        'Device_Type' => 'unknown',
        'Device_Pointing_Method' => 'unknown',
        'Device_Code_Name' => 'unknown',
        'Device_Brand_Name' => 'unknown',
        'RenderingEngine_Name' => 'unknown',
        'RenderingEngine_Version' => 'unknown',
        'RenderingEngine_Description' => 'unknown',
        'RenderingEngine_Maker' => 'unknown',
    ];

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        $objectIniPath = ini_get('browscap');

        if (!is_file($objectIniPath)) {
            self::markTestSkipped('browscap not defined in php.ini');
        }

        // Now, load an INI file into BrowscapPHP\Browscap for testing the UAs
        self::$object = new Browscap();

        $cacheAdapter = new Memory();
        $cache        = new BrowscapCache($cacheAdapter);
        $cache->flush();

        $updater = new BrowscapUpdater();
        $updater->setCache($cache);
        $updater->convertFile($objectIniPath);

        self::$object->setCache($cache);
    }

    /**
     * @group compare
     */
    public function testCheckProperties()
    {
        $libProperties = get_object_vars(get_browser('x'));
        $bcProperties  = get_object_vars(self::$object->getBrowser('x'));

        unset($libProperties['parent'], $bcProperties['parent']);

        $libPropertyKeys = array_keys($libProperties);
        $bcPropertyKeys  = array_keys($bcProperties);

        $diff = array_diff($libPropertyKeys, $bcPropertyKeys);

        if (!empty($diff)) {
            self::fail(
                'the properties found by "get_browser()" differ from found by "\BrowscapPHP\Browscap::getBrowser()" : '
                . serialize($diff)
            );
        }

        foreach (array_keys($this->properties) as $bcProp) {
            $bcProp = strtolower($bcProp);

            if (in_array($bcProp, ['browser_name_regex', 'browser_name_pattern', 'parent'])) {
                unset($libProperties[$bcProp]);
                continue;
            }

            self::assertArrayHasKey(
                $bcProp,
                $libProperties,
                'Property `' . $bcProp . '` from Browscap doesn\'t match anything in get_browser. '
                . 'You may have an outdated browscap.ini file for your tests'
            );

            unset($libProperties[$bcProp]);
        }

        self::assertSame(
            0,
            count($libProperties),
            'There are ' . count($libProperties) . '(' . implode(
                ', ',
                array_keys($libProperties)
            ) . ') properties in get_browser that do not match those in Browscap.'
        );
    }

    /**
     * @dataProvider providerUserAgent
     * @depends      testCheckProperties
     * @group        compare
     *
     * @param string $userAgent
     */
    public function testCompare($userAgent)
    {
        $libResult = get_browser($userAgent);
        $bcResult  = self::$object->getBrowser($userAgent);

        foreach (array_keys($this->properties) as $bcProp) {
            if (in_array($bcProp, ['browser_name_regex', 'browser_name_pattern', 'Parent'])) {
                continue;
            }

            $bcProp = strtolower($bcProp);

            self::assertObjectHasAttribute(
                $bcProp,
                $libResult,
                'Actual library result does not have "' . $bcProp . '" property'
            );

            self::assertObjectHasAttribute(
                $bcProp,
                $bcResult,
                'Actual browscap result does not have "' . $bcProp . '" property'
            );

            $libValue = strtolower((string) $libResult->{$bcProp});
            $bcValue  = strtolower((string) $bcResult->{$bcProp});

            self::assertSame(
                $libValue,
                $bcValue,
                'Expected actual "' . $bcProp . '" to be "' . $libValue . '" '
                . '(was "' . $bcValue . '"); ' . PHP_EOL
                . 'used pattern [\BrowscapPHP\Browscap::getBrowser()]:' . strtolower($bcResult->browser_name_pattern) . PHP_EOL
                . 'expected pattern [get_browser]:                    ' . strtolower($libResult->browser_name_pattern)
            );
        }
    }

    /**
     * @return array[]
     */
    public function providerUserAgent()
    {
        return [
            ['BlackBerry7100i/4.1.0 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/103'],
            ['check_http/v1.4.15 (nagios-plugins 1.4.15)'],
            ['facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'],
            ['Googlebot/2.1 (+http://www.googlebot.com/bot.html)'],
            ['HTC_Dream Mozilla/5.0 (Linux; U; Android 1.5; en-ca; Build/CUPCAKE) AppleWebKit/528.5+ (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1'],
            ['HTC_Touch_HD_T8282 Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 7.11)'],
            ['ichiro/3.0 (http://search.goo.ne.jp/option/use/sub4/sub4-1/)'],
            ['KDDI-KC31 UP.Browser/6.2.0.5 (GUI) MMP/2.0'],
            ['LG-CT810/V10x IEMobile/7.11 Profile/MIDP-2.0 Configuration/CLDC-1.1 Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 7.11)'],
            ['MOT 24.1 _/00.62 UP.Browser/6.2.3.4.c.1.120 (GUI) MMP/2.0'],
            ['Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; chromeframe/28.0.1500.72; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; MATM)'],
            ['Mozilla/5.0 (Android; Mobile; rv:19.0) Gecko/19.0 Firefox/19.0'],
            ['Mozilla/5.0 (BlackBerry; U; BlackBerry 9700; en-US) AppleWebKit/534.8+ (KHTML, like Gecko) Version/6.0.0.448 Mobile Safari/534.8+'],
            ['Mozilla/5.0 (compatible; MSIE 10.0; Windows Phone 8.0; Trident/6.0; IEMobile/10.0; ARM; Touch; NOKIA; Lumia 822)'],
            ['Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0; SAMSUNG; GT-S7530)'],
            ['Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) CriOS/27.0.1453.10 Mobile/10A403 Safari/8536.25'],
            ['Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A407 Safari/8536.25'],
            ['Mozilla/5.0 (iPad; U; CPU OS 4_3_5 like Mac OS X; es-es) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8L1 Safari/6533.18.5'],
            ['Mozilla/5.0 (iPod; CPU iPhone OS 5_1_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B206 Safari/7534.48.3'],
            ['Mozilla/5.0 (Linux; Android 4.0.4; GT-P5100 Build/IMM76D) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.166  Safari/535.19'],
            ['Mozilla/5.0 (Linux; Android 4.1.2; GT-I9300 Build/JZO54K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.63 Mobile Safari/537.36 OPR/15.0.1162.60140'],
            ['Mozilla/5.0 (Linux; U; Android 4.0.4; pl-pl; HTC_DesireS_S510e Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30'],
            ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/534.52.7 (KHTML, like Gecko) Version/5.1.2 Safari/534.52.7'],
            ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31'],
            ['Mozilla/5.0 (SAMSUNG; SAMSUNG-GT-S8500/S8500PMLB2; U; Bada/2.0; pl-pl) AppleWebKit/534.20 (KHTML, like Gecko) Dolfin/3.0 Mobile WVGA SMM-MMS/1.2.0 OPN-B'],
            ['Mozilla/5.0 (SymbianOS/9.4; Series60/5.0 Nokia5230/40.0.003; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/525 (KHTML, like Gecko) Version/3.0 BrowserNG/7.2.7.4 3gpp-gba'],
            ['Mozilla/5.0 (Windows NT 5.1; rv:6.0.1) Gecko/20100101 Firefox/6.0.1'],
            ['Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.96 Safari/537.4'],
            ['Mozilla/5.0 (Windows NT 6.0; rv:24.0) Gecko/20130719 Firefox/24.0'],
            ['Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.68 Safari/537.36'],
            ['Mozilla/5.0 (X11; Linux x86_64; rv:21.0) Gecko/20100101 Firefox/21.0'],
            ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.64 Safari/537.36'],
            ['Opera/9.80 (J2ME/MIDP; Opera Mini/7.1.32052/30.3341; U; pl) Presto/2.8.119 Version/11.10'],
            ['Opera/9.80 (Windows NT 5.1; U; pl) Presto/2.9.168 Version/11.52'],
            ['Opera/9.80 (Windows NT 6.0; U; pl) Presto/2.10.289 Version/12.00'],
            ['Outlook-Express/7.0 (MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; McAfee; AskTbORJ/5.15.23.36191; TmstmpExt)'],
            ['SAMSUNG-SGH-A867/A867UCHJ3 SHP/VPP/R5 NetFront/35 SMM-MMS/1.2.0 profile/MIDP-2.0 configuration/CLDC-1.1 UP.Link/6.3.0.0.0'],
            ['WordPress/3.5.1; http://greenconsulting.ecolivingfan.info'],
            ['Der gro\\xdfe BilderSauger 2.00u'],
            ['\\x22Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)\\x22'],
            ['Mozilla/4.0 (compatible; MSIE 4.01; Mac_PowerPC)'],
            ['Mozilla/5.0 (SymbianOS/9.4; U; Series60/5.0 Nokia5800d-1/21.0.025; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (Linux; U; Android 2.3.7; de-de; HTC DESIRE HD Build/GRI40; SUNDAWG CM7) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) SP; 240x320; HTC_MTeoR/1.0 Profile/MIDP-2.0 Configuration/CLDC-1.1'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) PPC; 240x320; HTC_P3300/1.0 Profile/MIDP-2.0 Configuration/CLDC-1.1'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) PPC; 240x320; HTC_P3350/1.0 Profile/MIDP-2.0 Configuration/CLDC-'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) PPC; 240x320; HTC_P6300/1.0 Profile/MIDP-2.0 Configuration/CLDC-1.1'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) PPC; 640x480; HTC_X7500/1.0 Profile/MIDP-2.0 Configuration/CLDC-1.1'],
            ['Mozilla/5.0 (Linux; U; Android 2.3.3; de-de; HTCS510e/1.0 Android/2.2 release/06.23.2010 Browser/WAP 2.0 Profile/MIDP-2.0 Configuration/CLDC-1.1 Build/GRI40) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1'],
            ['Mozilla/5.0 (Linux; U; Android 2.3.3; en-cn; HTCS710e/1.0 Android/2.2 release/06.23.2010 Browser/WAP 2.0 Profile/MIDP-2.0 Configuration/CLDC-1.1 Build/GRI40) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) HTC-8500/1.2'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) PPC; 240x320; HTC_P3350/1.0 Profile/MIDP-2.0 Configuration/CLDC-1.1'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) HTCS620'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) Vodafone/1.0/HTC_v1510/1.23.162.2'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) Vodafone/1.0/HTC_v3600/1.23.164.7'],
            ['Mozilla/5.0 (Linux; U; Android 2.2.2; en-us; HUAWEI T8600 Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) FlyFlow/1.0 Version/4.0 Mobile Safari/533.1'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 LG KS10/v10A; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 LGKT615/v10A; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 LG-KT770/v08V; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (Linux; Android 3.1; pt-PT; MZ606 Build/UMWB8E) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.166 Mobile Safari/535.19'],
            ['Mozilla/5.0 (SymbianOS/9.2 U Series60/3.1 NokiaN76-1/20.0.041 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaN78-2/1.00 Profile/MIDP-2.0 Configuration/CLDC-1.1) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaN79-1/10.034; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaN79-3/10.018; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.1; Series60/3.0 NokiaN80-1/3.0; Profile/MIDP-2.0 Configuration/CLDC-1.1) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaN81-1/10.0.026 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaN81-3/10.0.032 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/4'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaN85-1/10.034; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaN95_8GB-3/1.2.011 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaN95-3/10.2.003; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaN96-3/1.00; Profile/MIDP-2.1 Configuration/CLDC-1.1;) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.4; U; Series60/5.0 Nokia5233-2G/10.0.055; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413 Schemas used: uaprof, MMS, streaming'],
            ['Mozilla/5.0 (SymbianOS/9.3 U Series60/3.2 Nokia5320d-1/1.00 Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 Nokia5320d-1b/04.13; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 Nokia5320di XpressMusic/06.103; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Nokia6120/1.0; Profile/MIDP-2.0 Configuration/CLDC-1.1) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 Nokia6210Navigator/03.03.1; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3 U Series60/3.2 Nokia6650d-1c/03.09 Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaE-90-1/07.02.4.1; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.1; U; [de]; NokiaE50-1/06.41.3.0 Series60/3.0) AppleWebKit/413 (KHTML, like Gecko) Safari/413,gzip(gfe),gzip(gfe)'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaE51-2/151.34.20; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.1; Series60/3.0 NokiaE61i-1/3.0; Profile/MIDP-2.0 Configuration/CLDC-1.1) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaE63-1/200.21.012; Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaE63-2/100.21.110; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaE63-3/410.21.010; Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaE66-1/102.07.81; Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaE66-3/102.07.81; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaE71-1/200.21.118; Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2 U Series60/3.1 NokiaE90-1/210.34.75 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NOKIAN81 8GB/1.0; Profile/MIDP-2.0 Configuration/CLDC-1.1) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (Linux; Android 3.2; en-US; SHW-M305W Build/P2FHU4) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.166 Mobile Safari/535.19'],
            ['Mozilla/5.0 (SymbianOS/9.3 U Series60/3.2 Samsung/I8510/XXHH6 Profile/MIDP -2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 Samsung/I8510L/UBHL3 Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 Samsung/I8510M/UBHL2 Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2 U Series60/3.1 Samsung/SGH-G810/XEHA3 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung/SGH-i450V/BUGJ6 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung/SGH-i455/UMHA3 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, Like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2 U Series60/3.1 Samsung/SGH-i458B/ Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung/SGH-i520V/BUGD9 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung-SGH-i550/AOGL2 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung/SGH-i550V/BUGJ5 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung-SGH-i560/BGHA1 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung/SGH-i560V/BUGH1 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 1.0/SamsungSGHi560/I560DFHC1 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 1.0/SamsungSGHi568/I568ZTHA1 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 SAMSUNG-GT-I8510C/1.0; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung/SGH-G810/XEHA3 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung/SGH-i450/XEGK5 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung/SGH-i520/XEGH1 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Samsung/SGH-i550/XEGK3; Profile/MIDP-2.0 Configuration/CLDC-1.1) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) SAMSUNG-SGH-i601/WM534'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Vodafone/1.0/SamsungSGHi560/I560AEHB1 Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaN95/12.0.013; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.1; U; [tr]; NokiaN73-1/3.0638.0.0.1 Series60/3.0) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaE51-1/100.34.20; Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaE63-1/100.21.110; Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaE71-1/100.07.76; Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaE90-1/200.34.73 Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaN76-1/31.0.014 Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaN81-1/11.0.045 Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaN82/11.0.117; Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaN95-3/10.2.006; Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 NokiaN95_8GB/15.0.015; Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Nokia5250/10.0.021; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 Nokia6124c/4.34; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 Nokia5320d-1/03.26; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 Nokia5730s-1/100.48.122; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 Nokia6220c-1/03.23; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 Nokia6790s-1c/03.38; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaE75-1/100.48.78 Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaN78-1/10.136; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaN79-1/11.049; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaN85-1/10.045; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaN96-1/1.20; Profile/MIDP-2.1 Configuration/CLDC-1.1;) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 NokiaN96-3/3.00; Profile/MIDP-2.1 Configuration/CLDC-1.1;) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.4; Series60/5.0 NokiaN97-5/30.2.004; Profile/MIDP-2.1 Configuration/CLDC-1.1) AppleWebKit/533.4 (KHTML, like Gecko) Safari/525'],
            ['Mozilla/5.0 (SymbianOS/9.4; U; Series60/5.0 Nokia5230-1b/10.2.071; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.4; U; Series60/5.0 Nokia5235/12.6.092; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.4; U; Series60/5.0 Nokia5530c-2/10.0.050; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/525 (KHTML, like Gecko) Safari/525'],
            ['Mozilla/5.0 (SymbianOS/9.4; U; Series60/5.0 NokiaX6-00/10.0.069; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.1; U; xx) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.3; U; Series60/3.2 Samsung/I8510/XXHG5; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; Series60/3.1 Samsung/SGH-i550/XXHH1 Profile/MIDP-2.0 Configuration/CLDC-1.1 U; ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 LGKT615/v10C; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) DopodD810'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 6.8) PPC; 240x320; MDA Vario/1.3 Profile/MIDP-2.0 Configuration/CLDC-1.1'],
            ['Windows-RSS-Platform/2.0 (MSIE 9.0; Windows NT 6.0)'],
            ['Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaE66-1/500.21.009; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413'],
            ['Mozilla/5.0 (Linux; U; Android 2.3.3; en-cn; HTCA510e/1.0 Android/2.4 release/02.25.2011 Browser/WAP 2.0 Profile/MIDP-2.0 Configuration/CLDC-1.1 Build/GRI40) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1'],
            ['NetFront/3.5.1(BREW 3.1.5; U; en-us; SAMSUNG; NetFront/3.1.5/WAP) PLS-M350 MMP/2.0 Profile/MIDP-2.1 Configuration/CLDC-1.1'],
            ['NetFront/3.5.1(BREW 3.1.5; U; en-us; SAMSUNG; NetFront/3.1.5/WAP) Sprint M350 MMP/2.0 Profile/MIDP-2.1 Configuration/CLDC-1.1'],
            ['NetFront/3.5.1(BREW 3.1.5; U; en-us; SAMSUNG; NetFront/3.1.5/WAP) Sprint M380 MMP/2.0 Profile/MIDP-2.1 Configuration/CLDC-1.1'],
            ['NetFront/3.5.1(BREW 3.1.5; U; en-us; SAMSUNG; NetFront/3.1.5/AMB) Sprint M550 MMP/2.0 Profile/MIDP-2.1 Configuration/CLDC-1.1'],
            ['NetFront/3.5.1(BREW 3.1.5; U; en-us; SAMSUNG; NetFront/3.1.5/AMB) Sprint M560 MMP/2.0 Profile/MIDP-2.1 Configuration/CLDC-1.1'],
            ['Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.6) Gecko/20040206 Mozilla/5.0 StumbleUpon/1.904'],
            ['Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 4.0; -D-H1-MS318089))'],
            ['Mozilla/5.0 (Linux; U; Android 1.5.1.16-RT-20120531.214856; xx; K-Touch E619 Build/AliyunOs-2012) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 UCBrowser/9.8.1.447 U3/0.8.0 Mobile Safari/533.1'],
            ['Mozilla/5.0 (Linux; U; Android v1.02_14.13-M_EN-2011.01.13; en_us) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Ninesky-android-mobile/2.1.0 Safari/533.1'],
            ['NetFront/3.5.1(BREW 3.1.5; U; xx; SAMSUNG; NetFront/3.1.5/AMB) Sprint M550 MMP/2.0 Profile/MIDP-2.1 Configuration/CLDC-1.1'],
            ['Mozilla/5.0 (Windows; U; Windows NT 6.1; en-GB; rv:1.9.1.17) Gecko/20110123 Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070225 lolifox/0.32'],
            ['Mozilla/4.0 (compatible; MSIE 4.01; Windows NT 5.1; .NET CLR 2.0.50727)'],
            ['Mozilla/4.0 (compatible; MSIE 4.0; Windows NT 4.0; .NET CLR 2.0.50727)'],
            ['Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; WOW64; Trident/8.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 3.0.30729; .NET CLR 3.5.30729; InfoPath.2; 360SE; ; 11sungb_AAPF0A95321C6D)'],
            ['Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.2; WOW64; Trident/8.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 3.0.30729; .NET CLR 3.5.30729)'],
            ['Mozilla/5.0 (compatible; MSIE 10.0; AOL 9.7; AOLBuild 4343.55; Windows NT 6.1; WOW64; Trident/8.0)'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; GT-N5110 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Safari/537.36 [FB_IAB/FB4A;FBAV/108.0.0.17.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; GT-N5110 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Safari/537.36 [FB_IAB/FB4A;FBAV/109.0.0.15.71;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; GT-N5110 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Safari/537.36 [FB_IAB/FB4A;FBAV/110.0.0.15.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; GT-N5110 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Safari/537.36 [FB_IAB/FB4A;FBAV/111.0.0.18.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G870A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/108.0.0.17.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G870A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/109.0.0.15.71;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G870A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/110.0.0.15.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G870A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/111.0.0.18.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G900A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/106.0.0.26.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G900A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/107.0.0.19.337;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G900A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/108.0.0.17.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G900A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/109.0.0.15.71;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G900A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/110.0.0.15.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G900A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/111.0.0.18.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G900A Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/98.0.0.18.70;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SAMSUNG-SM-G900AZ Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/109.0.0.15.71;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SCH-I605 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/106.0.0.26.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SCH-I605 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/107.0.0.19.337;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SCH-I605 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/108.0.0.17.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SCH-I605 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/109.0.0.15.71;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SCH-I605 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/110.0.0.15.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SCH-I605 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/111.0.0.18.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SCH-I605 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/46.0.0.26.153;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SPH-L720 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/108.0.0.17.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.2; SPH-L720 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/30.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/109.0.0.15.71;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G870A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/106.0.0.26.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G870A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/107.0.0.19.337;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G870A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/108.0.0.17.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G870A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/109.0.0.15.71;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G870A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/110.0.0.15.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G870A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/111.0.0.18.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G900A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/106.0.0.26.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G900A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/107.0.0.19.337;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G900A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/108.0.0.17.68;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G900A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/109.0.0.15.71;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G900A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/110.0.0.15.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G900A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/111.0.0.18.69;]'],
            ['Mozilla/5.0 (Linux; Android 4.4.4; SAMSUNG-SM-G900A Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/98.0.0.18.70;]'],
            ['Mozilla/5.0 (Linux; Android 6.0.1; 6045O; Build/MMB29M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 6.0.1; LGMS428; Build/MMB29M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 6.0.1; SAMSUNG-SM-G930A Build/MMB29M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36 ACHEETAHI/1'],
            ['Mozilla/5.0 (Linux; Android 6.0.1; SM-G920P Build/MMB29K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36 ACHEETAHI/1'],
            ['Mozilla/5.0 (Linux; Android 6.0.1; SM-G935P Build/MMB29M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36 ACHEETAHI/1'],
            ['Mozilla/5.0 (Linux; Android 6.0.1; SM-G935V Build/MMB29M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36 ACHEETAHI/1'],
            ['Mozilla/5.0 (Linux; Android 6.0.1; SM-N910T; Build/MMB29M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 6.0.1; SM-N920V Build/MMB29K; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36 ACHEETAHI/1'],
            ['Mozilla/5.0 (Linux; Android 6.0.1; SM-S320VL Build/MMB29M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/55.0.2883.91 Mobile Safari/537.36 ACHEETAHI/1'],
            ['Mozilla/5.0 (Linux; Android 7.0; 2PYB2 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.124 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-H830 Build/NRD90U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-H830 Build/NRD90U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.85 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-H910 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-H918 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-H918 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.124 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-H918 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.85 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-LS997 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-LS997 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.68 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-LS997 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.85 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-M210 Build/NRD90U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.124 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-US996 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LG-US996 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.124 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LGLS775 Build/NRD90U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LGLS775 Build/NRD90U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.85 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LGLS992 Build/NRD90U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LGLS992 Build/NRD90U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.85 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; LGMS210 Build/NRD90U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.124 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; Nexus 6 Build/NBD91X) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.68 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; VS987 Build/NRD90U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.124 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; VS987 Build/NRD90U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.85 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; VS995 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.85 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; XT1650 Build/NCL25.86-11) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; XT1650 Build/NCL25.86-11) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.85 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; XT1650 Build/NCLS25.86-11-4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.98 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.0; XT1650 Build/NCLS25.86-11-4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.85 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.1.1; Nexus 6P Build/N4F26I) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.135 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.1.1; Pixel Build/NMF26U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.135 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.1.1; Pixel Build/NMF26U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.85 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; Android 7.1.1; Pixel XL Build/NMF26U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.135 Mobile Safari/537.36'],
            ['Mozilla/5.0 (Linux; U; Android 4.1.1; en-us; ME172V Build/JRO03H) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30 [FB_IAB/FB4A;FBAV/110.0.0.15.69;]'],
            ['Mozilla/5.0 (Linux; U; Android 4.2.2; en-us; A/S7 Build/AS7) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30 Maxthon/4.0.4.1000'],
            ['Mozilla/5.0 (Linux; U; Android 5.0.2; en-us; Lenovo A6000 Build/LRX22G) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 UCBrowser/9.9.2.467 U3/0.8.0 Mobile Safari/534.30'],
            ['Mozilla/5.0 (Linux; U; Android 5.0; en-us; Lenovo A7000-a Build/LRX21M) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 UCBrowser/9.9.2.467 U3/0.8.0 Mobile Safari/534.30'],
            ['Mozilla/5.0 (Linux; U; Android 5.1; en-us; Lenovo A2010-a Build/LMY47D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 UCBrowser/9.9.2.467 U3/0.8.0 Mobile Safari/534.30'],
            ['nokia6120c/UC Browser8.0.3.107/69/444 UNTRUSTED/1.0'],
        ];
    }
}
