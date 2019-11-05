<?php
declare(strict_types = 1);

namespace BrowscapPHP\Data;

final class PropertyFormatter
{
    /**
     * @var PropertyHolderInterface
     */
    private $propertyHolder;

    /**
     * class constructor
     *
     * @param PropertyHolderInterface $propertyHolder
     */
    public function __construct(PropertyHolderInterface $propertyHolder)
    {
        $this->propertyHolder = $propertyHolder;
    }

    /**
     * formats the name of a property
     *
     * @param bool|string $value
     * @param string      $property
     *
     * @throws \InvalidArgumentException
     *
     * @return bool|string
     */
    public function formatPropertyValue($value, string $property)
    {
        if (PropertyHolder::TYPE_BOOLEAN !== $this->propertyHolder->getPropertyType($property)) {
            return $value;
        }

        if (true === $value || 'true' === $value || '1' === $value) {
            return true;
        }

        return false;
    }
}
