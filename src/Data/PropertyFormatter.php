<?php
declare(strict_types=1);

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
     * @param string|bool $value
     * @param string $property
     *
     * @return string|bool
     * @throws \Exception
     */
    public function formatPropertyValue($value, string $property)
    {
        switch ($this->propertyHolder->getPropertyType($property)) {
            case PropertyHolder::TYPE_BOOLEAN:
                if (true === $value || $value === 'true' || $value === '1') {
                    return true;
                }
                return false;
                break;
            case PropertyHolder::TYPE_IN_ARRAY:
                try {
                    return $this->propertyHolder->checkValueInArray($property, $value);
                } catch (\InvalidArgumentException $ex) {
                    return '';
                }
                break;
        }

        return $value;
    }
}
