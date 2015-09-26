<?php
namespace phpbrowscapTest;

use phpbrowscap\Browscap;

/**
 * Compares get_browser results for all matches in browscap.ini with results from Browscap class.
 * Also compares the execution times.
 *
 * @group compare-with-native-function
 */
class CompareBrowscapWithOriginalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Browscap
     */
    private static $object = null;

    /**
     * @var string
     */
    private static $cacheDir = null;

    /**
     * @var array
     */
    private $properties = array(
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
    );

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass()
    {
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'browscap_testing';

        if (!is_dir($cacheDir)) {
            if (false === @mkdir($cacheDir, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create the "%s" directory', $cacheDir));
            }
        }

        self::$cacheDir = $cacheDir;

        $objectIniPath = ini_get('browscap');

        if (!is_file($objectIniPath)) {
            self::markTestSkipped('browscap not defined in php.ini');
        }

        self::$object               = new Browscap(self::$cacheDir);
        self::$object->localFile    = $objectIniPath;
        self::$object->doAutoUpdate = false;
        self::$object->updateCache();
    }

    /**
     * @throws \Exception
     * @throws \phpbrowscap\Exception
     * @group check-properties
     */
    public function testCheckProperties()
    {
        $libProperties = get_object_vars(get_browser('x'));
        $bcProperties  = get_object_vars(self::$object->getBrowser('x'));

        unset($bcProperties['Parents']);
        unset($bcProperties['browser_name']);
        unset($libProperties['browser_name']);
        unset($bcProperties['RenderingEngine_Description']);
        unset($libProperties['renderingengine_description']);

        $libPropertyKeys = array_map('strtolower', array_keys($libProperties));
        $bcPropertyKeys  = array_map('strtolower', array_keys($bcProperties));

        self::assertEquals($libPropertyKeys, $bcPropertyKeys);

        foreach (array_keys($bcProperties) as $bcProp) {
            self::assertArrayHasKey(
                strtolower($bcProp),
                $libProperties,
                'Property `' . $bcProp . '` from Browscap doesn\'t match anything in get_browser.'
            );

            unset($libProperties[strtolower($bcProp)]);
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
     * @depends      testCheckProperties
     *
     * @param string $userAgent
     *
     * @throws \Exception
     * @throws \phpbrowscap\Exception
     */
    public function testCompare($userAgent)
    {
        $libResult = get_browser($userAgent);
        $bcResult  = self::$object->getBrowser($userAgent);

        $doNotCheck = array('browser_name_regex', 'browser_name_pattern', 'Parent', 'RenderingEngine_Description');

        foreach (array_keys($this->properties) as $bcProp) {
            if (in_array($bcProp, $doNotCheck)) {
                continue;
            }

            $libProp = strtolower($bcProp);

            $libValue = (string) $libResult->{$libProp};
            $bcValue  = (string) $bcResult->{$bcProp};

            self::assertSame(
                $libValue,
                $bcValue,
                'Expected actual "' . $bcProp . '" to be "' . (string) $libValue . '" (was "'
                . (string) $bcValue
                . '"; used pattern: ' . (string) $bcResult->browser_name_pattern .')'
            );
        }
    }

    public function providerUserAgent()
    {
        return array(
            array('BlackBerry7100i/4.1.0 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/103'),
            array('check_http/v1.4.15 (nagios-plugins 1.4.15)'),
            array('facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'),
            array('Googlebot/2.1 (+http://www.googlebot.com/bot.html)'),
            array('HTC_Dream Mozilla/5.0 (Linux; U; Android 1.5; en-ca; Build/CUPCAKE) AppleWebKit/528.5+ (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1'),
            array('HTC_Touch_HD_T8282 Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 7.11)'),
            array('ichiro/3.0 (http://search.goo.ne.jp/option/use/sub4/sub4-1/)'),
            array('KDDI-KC31 UP.Browser/6.2.0.5 (GUI) MMP/2.0'),
            array('LG-CT810/V10x IEMobile/7.11 Profile/MIDP-2.0 Configuration/CLDC-1.1 Mozilla/4.0 (compatible; MSIE 6.0; Windows CE; IEMobile 7.11)'),
            array('MOT 24.1 _/00.62 UP.Browser/6.2.3.4.c.1.120 (GUI) MMP/2.0'),
            array('Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; chromeframe/28.0.1500.72; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; MATM)'),
            array('Mozilla/5.0 (Android; Mobile; rv:19.0) Gecko/19.0 Firefox/19.0'),
            array('Mozilla/5.0 (BlackBerry; U; BlackBerry 9700; en-US) AppleWebKit/534.8+ (KHTML, like Gecko) Version/6.0.0.448 Mobile Safari/534.8+'),
            array('Mozilla/5.0 (compatible; MSIE 10.0; Windows Phone 8.0; Trident/6.0; IEMobile/10.0; ARM; Touch; NOKIA; Lumia 822)'),
            array('Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0; SAMSUNG; GT-S7530)'),
            array('Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) CriOS/27.0.1453.10 Mobile/10A403 Safari/8536.25'),
            array('Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A407 Safari/8536.25'),
            array('Mozilla/5.0 (iPad; U; CPU OS 4_3_5 like Mac OS X; es-es) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8L1 Safari/6533.18.5'),
            array('Mozilla/5.0 (iPod; CPU iPhone OS 5_1_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9B206 Safari/7534.48.3'),
            array('Mozilla/5.0 (Linux; Android 4.0.4; GT-P5100 Build/IMM76D) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.166  Safari/535.19'),
            array('Mozilla/5.0 (Linux; Android 4.1.2; GT-I9300 Build/JZO54K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.63 Mobile Safari/537.36 OPR/15.0.1162.60140'),
            array('Mozilla/5.0 (Linux; U; Android 4.0.4; pl-pl; HTC_DesireS_S510e Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30'),
            array('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/534.52.7 (KHTML, like Gecko) Version/5.1.2 Safari/534.52.7'),
            array('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31'),
            array('Mozilla/5.0 (SAMSUNG; SAMSUNG-GT-S8500/S8500PMLB2; U; Bada/2.0; pl-pl) AppleWebKit/534.20 (KHTML, like Gecko) Dolfin/3.0 Mobile WVGA SMM-MMS/1.2.0 OPN-B'),
            array('Mozilla/5.0 (SymbianOS/9.4; Series60/5.0 Nokia5230/40.0.003; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/525 (KHTML, like Gecko) Version/3.0 BrowserNG/7.2.7.4 3gpp-gba'),
            array('Mozilla/5.0 (Windows NT 5.1; rv:6.0.1) Gecko/20100101 Firefox/6.0.1'),
            array('Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.96 Safari/537.4'),
            array('Mozilla/5.0 (Windows NT 6.0; rv:24.0) Gecko/20130719 Firefox/24.0'),
            array('Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.68 Safari/537.36'),
            array('Mozilla/5.0 (X11; Linux x86_64; rv:21.0) Gecko/20100101 Firefox/21.0'),
            array('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.64 Safari/537.36'),
            array('Opera/9.80 (J2ME/MIDP; Opera Mini/7.1.32052/30.3341; U; pl) Presto/2.8.119 Version/11.10'),
            array('Opera/9.80 (Windows NT 5.1; U; pl) Presto/2.9.168 Version/11.52'),
            array('Opera/9.80 (Windows NT 6.0; U; pl) Presto/2.10.289 Version/12.00'),
            array('Outlook-Express/7.0 (MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; McAfee; AskTbORJ/5.15.23.36191; TmstmpExt)'),
            array('SAMSUNG-SGH-A867/A867UCHJ3 SHP/VPP/R5 NetFront/35 SMM-MMS/1.2.0 profile/MIDP-2.0 configuration/CLDC-1.1 UP.Link/6.3.0.0.0'),
            array('WordPress/3.5.1; http://greenconsulting.ecolivingfan.info'),
            array('Der gro\\xdfe BilderSauger 2.00u'),
            array('\\x22Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)\\x22'),
        );
    }
}
