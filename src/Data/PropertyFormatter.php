<?php
declare(strict_types = 1);

namespace BrowscapPHP\Data;

final class PropertyFormatter
{
    /**
     * @var PropertyHolder
     */
    private $propertyHolder;

    /**
     * class constructor
     *
     * @param PropertyHolder $propertyHolder
     */
    public function __construct(PropertyHolder $propertyHolder)
    {
        $this->propertyHolder = $propertyHolder;
    }

    /**
     * formats the name of a property
     *
     * @param bool|string $value
     * @param string $property
     *
     * @return bool|string
     */
    public function formatPropertyValue($value, string $property)
    {
        switch ($this->propertyHolder->getPropertyType($property)) {
            case PropertyHolder::TYPE_BOOLEAN:
                if (true === $value || 'true' === $value || '1' === $value) {
                    return true;
                }

                return false;
            case PropertyHolder::TYPE_IN_ARRAY:
                try {
                    return $this->propertyHolder->checkValueInArray($property, (string) $value);
                } catch (\InvalidArgumentException $ex) {
                    // nothing to do here
                }

                return '';
        }

        return $value;
    }
}
