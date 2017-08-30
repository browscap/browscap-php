<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Util\LogFile;

use BrowscapPHP\Exception\ReaderException;
use BrowscapPHP\Util\Logfile\ReaderCollection;
use BrowscapPHP\Util\Logfile\ReaderInterface;

/**
 * @covers \BrowscapPHP\Util\Logfile\ReaderCollection
 */
final class ReaderCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ReaderCollection
     */
    private $object;

    public function setUp() : void
    {
        $this->object = new ReaderCollection();
    }

    public function testAddReader() : void
    {
        /** @var \BrowscapPHP\Util\Logfile\ReaderInterface|\PHPUnit_Framework_MockObject_MockObject $reader */
        $reader = $this->createMock(ReaderInterface::class);

        self::assertSame($this->object, $this->object->addReader($reader));
    }

    public function testTestSuccessful() : void
    {
        /** @var \BrowscapPHP\Util\Logfile\ReaderInterface|\PHPUnit_Framework_MockObject_MockObject $reader */
        $reader = $this->createMock(ReaderInterface::class);
        $reader
            ->expects(self::once())
            ->method('test')
            ->willReturn(true);

        $this->object->addReader($reader);

        self::assertTrue($this->object->test('Test'));
    }

    public function testTestNotSuccessful() : void
    {
        /** @var \BrowscapPHP\Util\Logfile\ReaderInterface|\PHPUnit_Framework_MockObject_MockObject $reader */
        $reader = $this->createMock(ReaderInterface::class);
        $reader
            ->expects(self::once())
            ->method('test')
            ->willReturn(false);

        $this->object->addReader($reader);

        self::assertFalse($this->object->test('Test'));
    }

    public function testReadSuccessful() : void
    {
        /** @var \BrowscapPHP\Util\Logfile\ReaderInterface|\PHPUnit_Framework_MockObject_MockObject $reader */
        $reader = $this->createMock(ReaderInterface::class);
        $reader
            ->expects(self::once())
            ->method('test')
            ->will(self::returnValue(true));
        $reader
            ->expects(self::once())
            ->method('read')
            ->will(self::returnValue('TestUA'));

        $this->object->addReader($reader);

        self::assertSame('TestUA', $this->object->read('Test'));
    }

    public function testReadNotSuccessful() : void
    {
        /** @var \BrowscapPHP\Util\Logfile\ReaderInterface|\PHPUnit_Framework_MockObject_MockObject $reader */
        $reader = $this->createMock(ReaderInterface::class);
        $reader
            ->expects(self::once())
            ->method('test')
            ->will(self::returnValue(false));
        $reader
            ->expects(self::never())
            ->method('read')
            ->will(self::returnValue('TestUA'));

        $this->object->addReader($reader);

        $this->expectException(ReaderException::class);
        $this->expectExceptionMessage('Cannot extract user agent string from line "Test"');
        $this->object->read('Test');
    }
}
