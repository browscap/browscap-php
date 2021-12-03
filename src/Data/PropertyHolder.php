<?php

declare(strict_types=1);

namespace BrowscapPHP\Data;

use function array_key_exists;

final class PropertyHolder
{
    public const TYPE_STRING  = 'string';
    public const TYPE_GENERIC = 'generic';
    public const TYPE_NUMBER  = 'number';
    public const TYPE_BOOLEAN = 'boolean';

    /**
     * Get the type of a property.
     *
     * @throws void
     */
    public function getPropertyType(string $propertyName): string
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

        return self::TYPE_GENERIC;
    }
}
