<?php
declare(strict_types = 1);

namespace BrowscapPHPTest;

use BrowscapPHP\Browscap;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception;
use BrowscapPHP\Formatter\FormatterInterface;
use BrowscapPHP\Parser\Ini;
use BrowscapPHP\Parser\ParserInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * @covers \BrowscapPHP\Browscap
 */
final class BrowscapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \BrowscapPHP\Browscap
     */
    private $object;

    protected function setUp() : void
    {
        /** @var CacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(CacheInterface::class);

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->object = new Browscap($cache, $logger);
    }

    public function testSetGetFormatter() : void
    {
        /** @var FormatterInterface|\PHPUnit_Framework_MockObject_MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);

        $this->object->setFormatter($formatter);

        $property = new \ReflectionProperty($this->object, 'formatter');
        $property->setAccessible(true);

        self::assertSame($formatter, $property->getValue($this->object));
    }

    public function testGetParser() : void
    {
        self::assertInstanceOf(Ini::class, $this->object->getParser());
    }

    public function testSetGetParser() : void
    {
        /** @var ParserInterface|\PHPUnit_Framework_MockObject_MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);

        $this->object->setParser($parser);
        self::assertSame($parser, $this->object->getParser());
    }

    public function testGetBrowserWithoutCache() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('there is no active cache available, please use the BrowscapUpdater and run the update command');
        $this->object->getBrowser();
    }

    public function testGetBrowserWithoutUa() : void
    {
        $expectedResult = new \stdClass();
        $expectedResult->parent = 'something';
        $expectedResult->comment = 'an comment';

        /** @var FormatterInterface|\PHPUnit_Framework_MockObject_MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('getData')->willReturn($expectedResult);

        /** @var ParserInterface|\PHPUnit_Framework_MockObject_MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(self::once())->method('getBrowser')->willReturn($formatter);

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);

        $reflection = new \ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $result = $this->object->getBrowser();

        self::assertSame($expectedResult, $result);
    }

    public function testGetBrowserWithUa() : void
    {
        $expectedResult = new \stdClass();
        $expectedResult->parent = 'something';
        $expectedResult->comment = 'an comment';

        /** @var FormatterInterface|\PHPUnit_Framework_MockObject_MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('getData')->willReturn($expectedResult);

        /** @var ParserInterface|\PHPUnit_Framework_MockObject_MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(self::once())->method('getBrowser')->willReturn($formatter);

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);

        $reflection = new \ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        self::assertSame($expectedResult, $result);
    }

    public function testGetBrowserWithDefaultResult() : void
    {
        $expectedResult = new \stdClass();

        /** @var FormatterInterface|\PHPUnit_Framework_MockObject_MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('getData')->willReturn($expectedResult);

        /** @var ParserInterface|\PHPUnit_Framework_MockObject_MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(self::once())->method('getBrowser')->willReturn(null);

        /** @var BrowscapCacheInterface|\PHPUnit_Framework_MockObject_MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);

        $reflection = new \ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        self::assertSame($expectedResult, $result);
    }
}
