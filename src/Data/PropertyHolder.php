<?php
declare(strict_types = 1);

namespace BrowscapPHP\Data;

final class PropertyHolder
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
     * @return string
     */
    public function getPropertyType(string $propertyName) : string
    {
        $stringProperties = [
            'Comment' => 1,
            'Browser' => 1,
            'Browser_Maker' => 1,
            'Browser_Modus' => 1,
            'Platform' => 1,
            'Platform_Name' => 1,
            'Platform_Description' => 1,
            'Device_Name' => 1,
            'Platform_Maker' => 1,
            'Device_Code_Name' => 1,
            'Device_Maker' => 1,
            'Device_Brand_Name' => 1,
            'RenderingEngine_Name' => 1,
            'RenderingEngine_Description' => 1,
            'RenderingEngine_Maker' => 1,
            'Parent' => 1,
            'PropertyName' => 1,
            'CDF' => 1,
            'PatternId' => 1,
        ];

        if (array_key_exists($propertyName, $stringProperties)) {
            return self::TYPE_STRING;
        }

        $arrayProperties = [
            'Browser_Type' => 1,
            'Device_Type' => 1,
            'Device_Pointing_Method' => 1,
            'Browser_Bits' => 1,
            'Platform_Bits' => 1,
        ];

        if (array_key_exists($propertyName, $arrayProperties)) {
            return self::TYPE_IN_ARRAY;
        }

        $genericProperties = [
            'Platform_Version' => 1,
            'RenderingEngine_Version' => 1,
            'Released' => 1,
            'Format' => 1,
            'Type' => 1,
        ];

        if (array_key_exists($propertyName, $genericProperties)) {
            return self::TYPE_GENERIC;
        }

        $numericProperties = [
            'Version' => 1,
            'CssVersion' => 1,
            'AolVersion' => 1,
            'MajorVer' => 1,
            'MinorVer' => 1,
            'aolVersion' => 1,
        ];

        if (array_key_exists($propertyName, $numericProperties)) {
            return self::TYPE_NUMBER;
        }

        $booleanProperties = [
            'Alpha' => 1,
            'Beta' => 1,
            'Win16' => 1,
            'Win32' => 1,
            'Win64' => 1,
            'Frames' => 1,
            'IFrames' => 1,
            'Tables' => 1,
            'Cookies' => 1,
            'BackgroundSounds' => 1,
            'JavaScript' => 1,
            'VBScript' => 1,
            'JavaApplets' => 1,
            'ActiveXControls' => 1,
            'isMobileDevice' => 1,
            'isTablet' => 1,
            'isSyndicationReader' => 1,
            'Crawler' => 1,
            'MasterParent' => 1,
            'LiteMode' => 1,
            'isFake' => 1,
            'isAnonymized' => 1,
            'isModified' => 1,
            'isBanned' => 1,
            'supportsCSS' => 1,
            'AOL' => 1,
        ];

        if (array_key_exists($propertyName, $booleanProperties)) {
            return self::TYPE_BOOLEAN;
        }

        throw new \InvalidArgumentException("Property {$propertyName} did not have a defined property type");
    }

    /**
     * @param string $property
     * @param string $value
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function checkValueInArray(string $property, string $value) : string
    {
        switch ($property) {
            case 'Browser_Type':
                $allowedValues = [
                    'Useragent Anonymizer' => 1,
                    'Browser' => 1,
                    'Offline Browser' => 1,
                    'Multimedia Player' => 1,
                    'Library' => 1,
                    'Feed Reader' => 1,
                    'Email Client' => 1,
                    'Bot/Crawler' => 1,
                    'Application' => 1,
                    'Tool' => 1,
                    'unknown' => 1,
                ];

                break;
            case 'Device_Type':
                $allowedValues = [
                    'Console' => 1,
                    'TV Device' => 1,
                    'Tablet' => 1,
                    'Mobile Phone' => 1,
                    'Smartphone' => 1,    // actual mobile phone with touchscreen
                    'Feature Phone' => 1, // older mobile phone
                    'Mobile Device' => 1,
                    'FonePad' => 1,       // Tablet sized device with the capability to make phone calls
                    'Desktop' => 1,
                    'Ebook Reader' => 1,
                    'Car Entertainment System' => 1,
                    'Digital Camera' => 1,
                    'unknown' => 1,
                ];

                break;
            case 'Device_Pointing_Method':
                // This property is taken from http://www.scientiamobile.com/wurflCapability
                $allowedValues = [
                    'joystick' => 1,
                    'stylus' => 1,
                    'touchscreen' => 1,
                    'clickwheel' => 1,
                    'trackpad' => 1,
                    'trackball' => 1,
                    'mouse' => 1,
                    'unknown' => 1,
                ];

                break;
            case 'Browser_Bits':
            case 'Platform_Bits':
                $allowedValues = [
                    '0' => 1,
                    '8' => 1,
                    '16' => 1,
                    '32' => 1,
                    '64' => 1,
                ];

                break;
            default:
                throw new \InvalidArgumentException('Property "' . $property . '" is not defined to be validated');
        }

        if (array_key_exists($value, $allowedValues)) {
            return $value;
        }

        throw new \InvalidArgumentException(
            'invalid value given for Property "' . $property . '": given value "' . $value . '", allowed: '
            . \ExceptionalJSON\encode($allowedValues)
        );
    }
}
