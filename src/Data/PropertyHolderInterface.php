<?php
declare(strict_types = 1);

namespace BrowscapPHP\Data;

interface PropertyHolderInterface
{
    public const TYPE_STRING = 'string';
    public const TYPE_GENERIC = 'generic';
    public const TYPE_NUMBER = 'number';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_IN_ARRAY = 'in_array';

    /**
     * Get the type of a property.
     *
     * @param string $propertyName
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getPropertyType(string $propertyName) : string;
}
