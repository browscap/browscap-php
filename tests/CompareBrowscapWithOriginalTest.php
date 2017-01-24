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
     * @var Browscap
     */
    private static $object = null;

    /**
     * @var array
     */
    private $properties = [
        'browser_name_regex'          => null,
        'browser_name_pattern'        => null,
        'Parent'                      => null,
        'Comment'                     => 'Default Browser',
        'Browser'                     => 'Default Browser',
        'Browser_Type'                => 'unknown',
        'Browser_Bits'                => '0',
        'Browser_Maker'               => 'unknown',
        'Browser_Modus'               => 'unknown',
        'Version'                     => '0.0',
        'MajorVer'                    => '0',
        'MinorVer'                    => '0',
        'Platform'                    => 'unknown',
        'Platform_Version'            => 'unknown',
        'Platform_Description'        => 'unknown',
        'Platform_Bits'               => '0',
        'Platform_Maker'              => 'unknown',
        'Alpha'                       => 'false',
        'Beta'                        => 'false',
        'Win16'                       => 'false',
        'Win32'                       => 'false',
        'Win64'                       => 'false',
        'Frames'                      => 'false',
        'IFrames'                     => 'false',
        'Tables'                      => 'false',
        'Cookies'                     => 'false',
        'BackgroundSounds'            => 'false',
        'JavaScript'                  => 'false',
        'VBScript'                    => 'false',
        'JavaApplets'                 => 'false',
        'ActiveXControls'             => 'false',
        'isMobileDevice'              => 'false',
        'isTablet'                    => 'false',
        'isSyndicationReader'         => 'false',
        'Crawler'                     => 'false',
        'CssVersion'                  => '0',
        'AolVersion'                  => '0',
        'IsFake'                      => 'false',
        'IsAnonymized'                => 'false',
        'IsModified'                  => 'false',
        'Device_Name'                 => 'unknown',
        'Device_Maker'                => 'unknown',
        'Device_Type'                 => 'unknown',
        'Device_Pointing_Method'      => 'unknown',
        'Device_Code_Name'            => 'unknown',
        'Device_Brand_Name'           => 'unknown',
        'RenderingEngine_Name'        => 'unknown',
        'RenderingEngine_Version'     => 'unknown',
        'RenderingEngine_Description' => 'unknown',
        'RenderingEngine_Maker'       => 'unknown',
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
            self::fail('the properties found by "get_browser()" differ from found by "Browser::getBrowser()"');
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
            'There are ' . count($libProperties) . '(' . implode(', ', array_keys($libProperties))
            . ') properties in get_browser that do not match those in Browscap.'
        );
    }

    /**
     * @dataProvider providerUserAgent
     * @depends testCheckProperties
     * @group compare
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

            $libValue = (string) $libResult->{$bcProp};
            $bcValue  = (string) $bcResult->{$bcProp};

            self::assertSame(
                $libValue,
                $bcValue,
                'Expected actual "' . $bcProp . '" to be "' . $libValue . '" (was "' . $bcValue
                . '"; used pattern: ' . $bcResult->browser_name_pattern . ')'
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
        ];
    }
}
