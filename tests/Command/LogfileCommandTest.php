<?php
declare(strict_types=1);

namespace BrowscapPHPTest\Command;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Command\LogfileCommand;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use WurflCache\Adapter\Memory;

/**
 * @covers \BrowscapPHP\Command\LogfileCommand
 */
final class LogfileCommandTest extends \PHPUnit_Framework_TestCase
{
    const STORAGE_DIR = 'storage';

    /**
     * @var LogfileCommand
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp() : void
    {
        $cacheAdapter   = new Memory();
        $cache          = new BrowscapCache($cacheAdapter);

        $this->object = new LogfileCommand('', $cache);
    }

    public function testConfigure() : void
    {
        $object = $this->getMockBuilder(LogfileCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['setName', 'setDescription', 'addArgument', 'addOption'])
            ->getMock();
        $object
            ->expects(self::once())
            ->method('setName')
            ->will(self::returnSelf());
        $object
            ->expects(self::once())
            ->method('setDescription')
            ->will(self::returnSelf());
        $object
            ->expects(self::once())
            ->method('addArgument')
            ->will(self::returnSelf());
        $object
            ->expects(self::exactly(6))
            ->method('addOption')
            ->will(self::returnSelf());

        $class  = new \ReflectionClass(LogfileCommand::class);
        $method = $class->getMethod('configure');
        $method->setAccessible(true);

        self::assertNull($method->invoke($object));
    }

    public function testExecute() : void
    {
        self::markTestSkipped('not ready yet');

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
            'test.log' => $content,
        ];

        vfsStream::setup(self::STORAGE_DIR, null, $structure);

        $map = [
            [
                'log-file',
                vfsStream::url(self::STORAGE_DIR . DIRECTORY_SEPARATOR . 'test.log'),
            ],
            [
                'debug',
                false,
            ],
            [
                'log-dir',
                null,
            ],
        ];

        $input  = $this->getMockBuilder(ArgvInput::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption', 'getArgument'])
            ->getMock();
        $input
            ->expects(self::exactly(5))
            ->method('getOption')
            ->will(self::returnValueMap($map));
        $input
            ->expects(self::once())
            ->method('getArgument')
            ->will(self::returnValue(vfsStream::url(self::STORAGE_DIR . DIRECTORY_SEPARATOR . 'test.txt')));

        $output = $this->getMockBuilder(ConsoleOutput::class)
            ->disableOriginalConstructor()
            ->getMock();

        $class  = new \ReflectionClass(LogfileCommand::class);
        $method = $class->getMethod('execute');
        $method->setAccessible(true);

        self::assertNull($method->invoke($this->object, $input, $output));
    }
}
