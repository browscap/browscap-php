<?php

declare(strict_types=1);

namespace BrowscapPHPTest;

use BrowscapPHP\Browscap;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception;
use BrowscapPHP\Formatter\FormatterInterface;
use BrowscapPHP\Parser\Ini;
use BrowscapPHP\Parser\ParserInterface;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use stdClass;

/**
 * @covers \BrowscapPHP\Browscap
 */
final class BrowscapTest extends TestCase
{
    private Browscap $object;

    /**
     * @throws void
     */
    protected function setUp(): void
    {
        $cache = $this->createMock(CacheInterface::class);

        $logger = $this->createMock(LoggerInterface::class);

        $this->object = new Browscap($cache, $logger);
    }

    /**
     * @throws ReflectionException
     */
    public function testSetGetFormatter(): void
    {
        $formatter = $this->createMock(FormatterInterface::class);

        $this->object->setFormatter($formatter);

        $property = new ReflectionProperty($this->object, 'formatter');
        $property->setAccessible(true);

        self::assertSame($formatter, $property->getValue($this->object));
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testGetParser(): void
    {
        self::assertInstanceOf(Ini::class, $this->object->getParser());
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testSetGetParser(): void
    {
        $parser = $this->createMock(ParserInterface::class);

        $this->object->setParser($parser);
        self::assertSame($parser, $this->object->getParser());
    }

    /**
     * @throws Exception
     */
    public function testGetBrowserWithoutCache(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('there is no active cache available, please use the BrowscapUpdater and run the update command');
        $this->object->getBrowser();
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testGetBrowserWithoutUa(): void
    {
        $expectedResult          = new stdClass();
        $expectedResult->parent  = 'something';
        $expectedResult->comment = 'an comment';

        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('getData')->willReturn($expectedResult);

        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(self::once())->method('getBrowser')->willReturn($formatter);

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $result = $this->object->getBrowser();

        self::assertSame($expectedResult, $result);
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testGetBrowserWithUa(): void
    {
        $expectedResult          = new stdClass();
        $expectedResult->parent  = 'something';
        $expectedResult->comment = 'an comment';

        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('getData')->willReturn($expectedResult);

        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(self::once())->method('getBrowser')->willReturn($formatter);

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        self::assertSame($expectedResult, $result);
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testGetBrowserWithDefaultResult(): void
    {
        $expectedResult = new stdClass();

        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(self::once())->method('getData')->willReturn($expectedResult);

        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(self::once())->method('getBrowser')->willReturn(null);

        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(self::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);

        $reflection         = new ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        self::assertSame($expectedResult, $result);
    }
}
