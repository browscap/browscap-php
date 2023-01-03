<?php

declare(strict_types=1);

namespace BrowscapPHP\Data;

final class PropertyFormatter
{
    private PropertyHolder $propertyHolder;

    /** @throws void */
    public function __construct(PropertyHolder $propertyHolder)
    {
        $this->propertyHolder = $propertyHolder;
    }

    /**
     * formats the name of a property
     *
     * @param bool|string $value
     *
     * @return bool|string
     *
     * @throws void
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function formatPropertyValue($value, string $property)
    {
        if ($this->propertyHolder->getPropertyType($property) === PropertyHolder::TYPE_BOOLEAN) {
            return $value === true || $value === 'true' || $value === '1';
        }

        return $value;
    }
}
