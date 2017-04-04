<?php
declare(strict_types = 1);

namespace BrowscapPHPTest;

use BrowscapPHP\Browscap;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception;
use BrowscapPHP\Formatter\FormatterInterface;
use BrowscapPHP\Parser\ParserInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WurflCache\Adapter\AdapterInterface;
use BrowscapPHP\Parser\Ini;

/**
 * @covers \BrowscapPHP\Browscap
 */
final class BrowscapTest extends \PHPUnit_Framework_TestCase
{
    const STORAGE_DIR = 'storage';

    /**
     * @var \BrowscapPHP\Browscap
     */
    private $object;

    public function setUp() : void
    {
        $this->object = new Browscap();
    }

    public function testSetGetFormatter() : void
    {
        /** @var FormatterInterface|\PHPUnit_Framework_MockObject_MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);

        self::assertSame($this->object, $this->object->setFormatter($formatter));
        self::assertSame($formatter, $this->object->getFormatter());
    }

    public function testGetCache() : void
    {
        self::assertInstanceOf(BrowscapCacheInterface::class, $this->object->getCache());
    }

    public function testSetGetCache() : void
    {
        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);

        self::assertSame($this->object, $this->object->setCache($cache));
        self::assertSame($cache, $this->object->getCache());
    }

    public function testSetGetCacheWithAdapter() : void
    {
        /** @var AdapterInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(AdapterInterface::class);

        self::assertSame($this->object, $this->object->setCache($cache));
        self::assertInstanceOf(BrowscapCacheInterface::class, $this->object->getCache());
    }

    public function testSetGetCacheWithWrongType() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'the cache has to be an instance of \BrowscapPHP\Cache\BrowscapCacheInterface or an instanceof of \WurflCache\Adapter\AdapterInterface'
        );

        /** @noinspection PhpParamsInspection */
        $this->object->setCache('test');
    }

    public function testGetParser() : void
    {
        self::assertInstanceOf(Ini::class, $this->object->getParser());
    }

    public function testSetGetParser() : void
    {
        /** @var ParserInterface|\PHPUnit_Framework_MockObject_MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);

        self::assertSame($this->object, $this->object->setParser($parser));
        self::assertSame($parser, $this->object->getParser());
    }

    public function testGetLogger() : void
    {
        self::assertInstanceOf(NullLogger::class, $this->object->getLogger());
    }

    public function testSetGetLogger() : void
    {
        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        self::assertSame($this->object, $this->object->setLogger($logger));
        self::assertSame($logger, $this->object->getLogger());
    }

    public function testGetBrowserWithoutCache() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('there is no active cache available, please run the update command');
        $this->object->getBrowser();
    }

    public function testGetBrowserWithoutUa() : void
    {
        $browserObject = new \StdClass();
        $browserObject->parent = 'something';
        $browserObject->comment = 'an comment';

        /** @var FormatterInterface|\PHPUnit_Framework_MockObject_MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('getData')->willReturn($browserObject);

        /** @var ParserInterface|\PHPUnit_Framework_MockObject_MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(self::once())->method('getBrowser')->willReturn($formatter);

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);
        $this->object->setCache($cache);
        $result = $this->object->getBrowser();

        self::assertSame($browserObject, $result);
    }

    public function testGetBrowserWithUa() : void
    {
        $browserObject = new \StdClass();
        $browserObject->parent = 'something';
        $browserObject->comment = 'an comment';

        /** @var FormatterInterface|\PHPUnit_Framework_MockObject_MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('getData')->willReturn($browserObject);

        /** @var ParserInterface|\PHPUnit_Framework_MockObject_MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(self::once())->method('getBrowser')->willReturn($formatter);

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);
        $this->object->setCache($cache);
        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        self::assertSame($browserObject, $result);
    }

    public function testGetBrowserWithDefaultResult() : void
    {
        /** @var FormatterInterface|\PHPUnit_Framework_MockObject_MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('getData')->willReturn(null);

        /** @var ParserInterface|\PHPUnit_Framework_MockObject_MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(self::once())->method('getBrowser')->willReturn(null);

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);
        $this->object->setCache($cache);
        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        self::assertNull($result);
    }
}
