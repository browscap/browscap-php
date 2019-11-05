<?php
declare(strict_types = 1);

namespace BrowscapPHPTest\Data;

use BrowscapPHP\Data\PropertyHolder;
use BrowscapPHP\Data\PropertyHolderInterface;

final class PropertyHolderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PropertyHolder
     */
    private $object;

    protected function setUp() : void
    {
        $this->object = new PropertyHolder();
    }

    /**
     * @dataProvider providerGetPropertyType
     *
     * @param string $propertyName
     * @param string $expected
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \InvalidArgumentException
     */
    public function testGetPropertyType(string $propertyName, string $expected) : void
    {
        static::assertSame($expected, $this->object->getPropertyType($propertyName));
    }

    public function providerGetPropertyType() : array
    {
        return [
            ['Comment', PropertyHolderInterface::TYPE_STRING],
            ['Browser_Type', PropertyHolderInterface::TYPE_IN_ARRAY],
            ['Platform_Version', PropertyHolderInterface::TYPE_GENERIC],
            ['Version', PropertyHolderInterface::TYPE_NUMBER],
            ['Alpha', PropertyHolderInterface::TYPE_BOOLEAN],
        ];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testGetPropertyTypeFails() : void
    {
        $propertyName = 'does-not-exist';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Property %s did not have a defined property type', $propertyName));

        $this->object->getPropertyType($propertyName);
    }
}
