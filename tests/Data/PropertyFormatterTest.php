<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Data;

use BrowscapPHP\Data\PropertyFormatter;
use BrowscapPHP\Data\PropertyHolderInterface;

final class PropertyFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \InvalidArgumentException
     */
    public function testFormatPropertyValueNonBolean() : void
    {
        $property = 'Comment';
        $value = 'x';

        $propertyHolder = $this->getMockBuilder(PropertyHolderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $propertyHolder
            ->expects(self::once())
            ->method('getPropertyType')
            ->with($property)
            ->willReturn(PropertyHolderInterface::TYPE_STRING);

        /** @var PropertyHolderInterface $propertyHolder */
        $object = new PropertyFormatter($propertyHolder);

        self::assertSame($value, $object->formatPropertyValue($value, $property));
    }

    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \InvalidArgumentException
     */
    public function testFormatPropertyValueBoleanTrue() : void
    {
        $property = 'Comment';
        $value = true;

        $propertyHolder = $this->getMockBuilder(PropertyHolderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $propertyHolder
            ->expects(self::once())
            ->method('getPropertyType')
            ->with($property)
            ->willReturn(PropertyHolderInterface::TYPE_BOOLEAN);

        /** @var PropertyHolderInterface $propertyHolder */
        $object = new PropertyFormatter($propertyHolder);

        self::assertTrue($object->formatPropertyValue($value, $property));
    }

    /**
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \InvalidArgumentException
     */
    public function testFormatPropertyValueBoleanFalse() : void
    {
        $property = 'Comment';
        $value = false;

        $propertyHolder = $this->getMockBuilder(PropertyHolderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $propertyHolder
            ->expects(self::once())
            ->method('getPropertyType')
            ->with($property)
            ->willReturn(PropertyHolderInterface::TYPE_BOOLEAN);

        /** @var PropertyHolderInterface $propertyHolder */
        $object = new PropertyFormatter($propertyHolder);

        self::assertFalse($object->formatPropertyValue($value, $property));
    }
}
