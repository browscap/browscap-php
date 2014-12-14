<?php
namespace BrowscapPHPTest;

use BrowscapPHP\Browscap;
use WurflCache\Adapter\Memory;
use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Parser\Ini;
use Browscap\Data\DataCollection;
use Browscap\Data\Expander;
use Browscap\Filter\FullFilter;
use Browscap\Formatter\PhpFormatter;
use Browscap\Helper\CollectionCreator;
use Browscap\Writer\IniWriter;
use Browscap\Writer\WriterCollection;
use Monolog\Handler\NullHandler;
use Monolog\Logger;

/**
 * Compares get_browser results for all matches in browscap.ini with results from Browscap class.
 * Also compares the execution times.
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
    private $properties = array();

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        self::markTestSkipped('not ready');

        if (class_exists('\Browscap\Browscap')) {
            // First, generate the INI files
            $resourceFolder = __DIR__ . '/../vendor/browscap/browscap/resources';

            $logger = new Logger('browscap');
            $logger->pushHandler(new NullHandler(Logger::DEBUG));

            $collectionCreator = new CollectionCreator();

            $collection = new DataCollection('test');
            $collection->setLogger($logger);

            $expander = new Expander();
            $expander
                ->setDataCollection($collection)
                ->setLogger($logger)
            ;

            $collectionCreator
                ->setLogger($logger)
                ->setDataCollection($collection)
                ->createDataCollection($resourceFolder)
            ;

            $writerCollection = new WriterCollection();
            $fullFilter       = new FullFilter();

            $fullPhpWriter = new IniWriter($objectIniPath);
            $formatter     = new PhpFormatter();
            $fullPhpWriter
                ->setLogger($logger)
                ->setFormatter($formatter->setFilter($fullFilter))
                ->setFilter($fullFilter)
            ;
            $writerCollection->addWriter($fullPhpWriter);

            $comments = array(
                'Provided courtesy of http://browscap.org/',
                'Created on ' . $collection->getGenerationDate()->format('l, F j, Y \a\t h:i A T'),
                'Keep up with the latest goings-on with the project:',
                'Follow us on Twitter <https://twitter.com/browscap>, or...',
                'Like us on Facebook <https://facebook.com/browscap>, or...',
                'Collaborate on GitHub <https://github.com/browscap>, or...',
                'Discuss on Google Groups <https://groups.google.com/forum/#!forum/browscap>.'
            );

            $writerCollection
                ->fileStart()
                ->renderHeader($comments)
                ->renderVersion('test', $collection)
            ;

            $writerCollection->renderAllDivisionsHeader($collection);

            $division = $collection->getDefaultProperties();

            $writerCollection->renderDivisionHeader($division->getName());

            $ua       = $division->getUserAgents();
            $sections = array($ua[0]['userAgent'] => $ua[0]['properties']);

            foreach ($sections as $sectionName => $section) {
                $writerCollection
                    ->renderSectionHeader($sectionName)
                    ->renderSectionBody($section, $collection, $sections)
                    ->renderSectionFooter()
                ;
            }

            $writerCollection->renderDivisionFooter();

            foreach ($collection->getDivisions() as $division) {
                /** @var \Browscap\Data\Division $division */
                $writerCollection->setSilent($division);

                $versions = $division->getVersions();

                foreach ($versions as $version) {
                    list($majorVer, $minorVer) = $expander->getVersionParts($version);

                    $userAgents = json_encode($division->getUserAgents());
                    $userAgents = $expander->parseProperty($userAgents, $majorVer, $minorVer);
                    $userAgents = json_decode($userAgents, true);

                    $divisionName = $expander->parseProperty($division->getName(), $majorVer, $minorVer);

                    $writerCollection->renderDivisionHeader($divisionName);

                    $sections = $expander->expand($division, $majorVer, $minorVer, $divisionName);

                    foreach ($sections as $sectionName => $section) {
                        $writerCollection
                            ->renderSectionHeader($sectionName)
                            ->renderSectionBody($section, $collection, $sections)
                            ->renderSectionFooter()
                        ;
                    }

                    $writerCollection->renderDivisionFooter();

                    unset($userAgents, $divisionName, $majorVer, $minorVer);
                }
            }

            $division = $collection->getDefaultBrowser();

            $writerCollection->renderDivisionHeader($division->getName());

            $ua       = $division->getUserAgents();
            $sections = array(
                $ua[0]['userAgent'] => array_merge(
                    array('Parent' => 'DefaultProperties'),
                    $ua[0]['properties']
                )
            );

            foreach ($sections as $sectionName => $section) {
                $writerCollection
                    ->renderSectionHeader($sectionName)
                    ->renderSectionBody($section, $collection, $sections)
                    ->renderSectionFooter()
                ;
            }

            $writerCollection
                ->renderDivisionFooter()
                ->renderAllDivisionsFooter()
            ;

            $writerCollection
                ->fileEnd()
                ->close()
            ;
        } else {
            $objectIniPath = ini_get('browscap');

            if (!is_file($objectIniPath)) {
                self::markTestSkipped('browscap not defined in php.ini');
            }
        }

        // Now, load an INI file into BrowscapPHP\Browscap for testing the UAs
        self::$object = new Browscap();

        $cacheAdapter = new Memory();
        $cache        = new BrowscapCache($cacheAdapter);

        self::$object
            ->setCache($cache)
            ->convertFile($objectIniPath)
        ;
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
    }

    public function testCheckProperties()
    {
        $libProperties = get_object_vars(get_browser('x'));
        $bcProperties  = get_object_vars(self::$object->getBrowser('x'));

        unset($bcProperties['Parents']);
        unset($bcProperties['browser_name']);
        unset($libProperties['browser_name']);
        unset($libProperties['renderingengine_description']);

        $libPropertyKeys = array_map('strtolower', array_keys($libProperties));
        $bcPropertyKeys  = array_map('strtolower', array_keys($bcProperties));

        self::assertSame(
            $libPropertyKeys,
            $bcPropertyKeys,
            'the properties found by "get_browser()" differ from found by "Browser::getBrowser()"'
        );

        foreach (array_keys($bcProperties) as $bcProp) {
            if (in_array($bcProp, array('browser_name', 'browser_name_regex', 'browser_name_pattern'))) {
                //continue;
            }

            self::assertArrayHasKey(
                strtolower($bcProp),
                $libProperties,
                'Property `' . $bcProp . '` from Browscap doesn\'t match anything in get_browser.'
            );

            if ('browser_name_regex' != $bcProp) {
                $this->properties[$bcProp] = strtolower($bcProp);
            }

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
     */
    public function testCompare($userAgent)
    {
        $libResult = get_browser($userAgent);
        $bcResult  = self::$object->getBrowser($userAgent);

        if ($userAgent == Ini::BROWSCAP_VERSION_KEY) {
            self::assertSame(
                $libResult->version,
                self::$object->getSourceVersion(),
                'Source file version incorrect: ' . $libResult->version . ' != '
                //. self::$object->getSourceVersion()
            );
        } else {
            foreach ($this->properties as $bcProp => $libProp) {
                $libValue = $libResult->{$libProp};
                $bcValue  = $bcResult->{$bcProp};

                self::assertSame(
                    $libValue,
                    $bcValue,
                    $bcProp . ': ' . $libValue . ' != ' . $bcValue
                );
            }
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
            array('\\x22Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)\\x22'),
        );
    }
}
