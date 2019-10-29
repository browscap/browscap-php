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

final class BrowscapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \BrowscapPHP\Browscap
     */
    private $object;

    /**
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    protected function setUp() : void
    {
        /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheInterface::class);

        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->object = new Browscap($cache, $logger);
    }

    /**
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testSetGetFormatter() : void
    {
        /** @var FormatterInterface|\PHPUnit\Framework\MockObject\MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);

        $this->object->setFormatter($formatter);

        $property = new \ReflectionProperty($this->object, 'formatter');
        $property->setAccessible(true);

        static::assertSame($formatter, $property->getValue($this->object));
    }

    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testGetParser() : void
    {
        static::assertInstanceOf(Ini::class, $this->object->getParser());
    }

    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testSetGetParser() : void
    {
        /** @var ParserInterface|\PHPUnit\Framework\MockObject\MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);

        $this->object->setParser($parser);
        static::assertSame($parser, $this->object->getParser());
    }

    /**
     * @throws \BrowscapPHP\Exception
     */
    public function testGetBrowserWithoutCache() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('there is no active cache available, please use the BrowscapUpdater and run the update command');
        $this->object->getBrowser();
    }

    /**
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \BrowscapPHP\Exception
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testGetBrowserWithoutUa() : void
    {
        $expectedResult = new \stdClass();
        $expectedResult->parent = 'something';
        $expectedResult->comment = 'an comment';

        /** @var FormatterInterface|\PHPUnit\Framework\MockObject\MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(static::once())->method('getData')->willReturn($expectedResult);

        /** @var ParserInterface|\PHPUnit\Framework\MockObject\MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(static::once())->method('getBrowser')->willReturn($formatter);

        /** @var BrowscapCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(static::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);

        $reflection = new \ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $result = $this->object->getBrowser();

        static::assertSame($expectedResult, $result);
    }

    /**
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \BrowscapPHP\Exception
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testGetBrowserWithUa() : void
    {
        $expectedResult = new \stdClass();
        $expectedResult->parent = 'something';
        $expectedResult->comment = 'an comment';

        /** @var FormatterInterface|\PHPUnit\Framework\MockObject\MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(static::once())->method('getData')->willReturn($expectedResult);

        /** @var ParserInterface|\PHPUnit\Framework\MockObject\MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(static::once())->method('getBrowser')->willReturn($formatter);

        /** @var BrowscapCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(static::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);

        $reflection = new \ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        static::assertSame($expectedResult, $result);
    }

    /**
     * @throws \ReflectionException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \BrowscapPHP\Exception
     * @throws \InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     */
    public function testGetBrowserWithDefaultResult() : void
    {
        $expectedResult = new \stdClass();

        /** @var FormatterInterface|\PHPUnit\Framework\MockObject\MockObject $formatter */
        $formatter = $this->createMock(FormatterInterface::class);
        $formatter->expects(static::once())->method('getData')->willReturn($expectedResult);

        /** @var ParserInterface|\PHPUnit\Framework\MockObject\MockObject $parser */
        $parser = $this->createMock(ParserInterface::class);
        $parser->expects(static::once())->method('getBrowser')->willReturn(null);

        /** @var BrowscapCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(BrowscapCacheInterface::class);
        $cache->expects(static::once())->method('getVersion')->willReturn(1);

        $this->object->setFormatter($formatter);
        $this->object->setParser($parser);

        $reflection = new \ReflectionClass($this->object);
        $reflectionAttrbute = $reflection->getProperty('cache');
        $reflectionAttrbute->setAccessible(true);
        $reflectionAttrbute->setValue($this->object, $cache);

        $result = $this->object->getBrowser('Mozilla/5.0 (compatible; Ask Jeeves/Teoma)');

        static::assertSame($expectedResult, $result);
    }
}
