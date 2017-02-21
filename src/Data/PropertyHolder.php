<?php
/**
 * Copyright (c) 1998-2015 Browser Capabilities Project.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   Browscap-PHP
 *
 * @copyright  1998-2015 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace BrowscapPHP\Data;

/**
 * Class PropertyHolder.
 *
 * @category   Browscap
 *
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 */
class PropertyHolder
{
    const TYPE_STRING   = 'string';
    const TYPE_GENERIC  = 'generic';
    const TYPE_NUMBER   = 'number';
    const TYPE_BOOLEAN  = 'boolean';
    const TYPE_IN_ARRAY = 'in_array';

    /**
     * Get the type of a property.
     *
     * @param string $propertyName
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getPropertyType($propertyName)
    {
        $stringProperties = [
            'Comment' => true,
            'Browser' => true,
            'Browser_Maker' => true,
            'Browser_Modus' => true,
            'Platform' => true,
            'Platform_Name' => true,
            'Platform_Description' => true,
            'Device_Name' => true,
            'Platform_Maker' => true,
            'Device_Code_Name' => true,
            'Device_Maker' => true,
            'Device_Brand_Name' => true,
            'RenderingEngine_Name' => true,
            'RenderingEngine_Description' => true,
            'RenderingEngine_Maker' => true,
            'Parent' => true,
            'PropertyName' => true,
            'CDF' => true,
        ];

        if (isset($stringProperties[$propertyName])) {
            return self::TYPE_STRING;
        }

        $arrayProperties = [
            'Browser_Type' => true,
            'Device_Type' => true,
            'Device_Pointing_Method' => true,
            'Browser_Bits' => true,
            'Platform_Bits' => true,
        ];

        if (isset($arrayProperties[$propertyName])) {
            return self::TYPE_IN_ARRAY;
        }

        $genericProperties = [
            'Platform_Version' => true,
            'RenderingEngine_Version' => true,
            'Released' => true,
            'Format' => true,
            'Type' => true,
        ];

        if (isset($genericProperties[$propertyName])) {
            return self::TYPE_GENERIC;
        }

        $numericProperties = [
            'Version' => true,
            'CssVersion' => true,
            'AolVersion' => true,
            'MajorVer' => true,
            'MinorVer' => true,
        ];

        if (isset($numericProperties[$propertyName])) {
            return self::TYPE_NUMBER;
        }

        $booleanProperties = [
            'Alpha' => true,
            'Beta' => true,
            'Win16' => true,
            'Win32' => true,
            'Win64' => true,
            'Frames' => true,
            'IFrames' => true,
            'Tables' => true,
            'Cookies' => true,
            'BackgroundSounds' => true,
            'JavaScript' => true,
            'VBScript' => true,
            'JavaApplets' => true,
            'ActiveXControls' => true,
            'isMobileDevice' => true,
            'isTablet' => true,
            'isSyndicationReader' => true,
            'Crawler' => true,
            'MasterParent' => true,
            'LiteMode' => true,
            'isFake' => true,
            'isAnonymized' => true,
            'isModified' => true,
        ];

        if (isset($booleanProperties[$propertyName])) {
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
    public function checkValueInArray($property, $value)
    {
        switch ($property) {
            case 'Browser_Type':
                $allowedValues = [
                    'Useragent Anonymizer' => true,
                    'Browser' => true,
                    'Offline Browser' => true,
                    'Multimedia Player' => true,
                    'Library' => true,
                    'Feed Reader' => true,
                    'Email Client' => true,
                    'Bot/Crawler' => true,
                    'Application' => true,
                    'Tool' => true,
                    'unknown' => true,
                ];
                break;
            case 'Device_Type':
                $allowedValues = [
                    'Console' => true,
                    'TV Device' => true,
                    'Tablet' => true,
                    'Mobile Phone' => true,
                    'Smartphone' => true,    // actual mobile phone with touchscreen
                    'Feature Phone' => true, // older mobile phone
                    'Mobile Device' => true,
                    'FonePad' => true,       // Tablet sized device with the capability to make phone calls
                    'Desktop' => true,
                    'Ebook Reader' => true,
                    'Car Entertainment System' => true,
                    'Digital Camera' => true,
                    'unknown' => true,
                ];
                break;
            case 'Device_Pointing_Method':
                // This property is taken from http://www.scientiamobile.com/wurflCapability
                $allowedValues = [
                    'joystick' => true,
                    'stylus' => true,
                    'touchscreen' => true,
                    'clickwheel' => true,
                    'trackpad' => true,
                    'trackball' => true,
                    'mouse' => true,
                    'unknown' => true,
                ];
                break;
            case 'Browser_Bits':
            case 'Platform_Bits':
                $allowedValues = [
                    '0' => true,
                    '8' => true,
                    '16' => true,
                    '32' => true,
                    '64' => true,
                ];
                break;
            default:
                throw new \InvalidArgumentException('Property "' . $property . '" is not defined to be validated');
                break;
        }

        if (isset($allowedValues[$value])) {
            return $value;
        }

        throw new \InvalidArgumentException(
            'invalid value given for Property "' . $property . '": given value "' . (string) $value . '", allowed: '
            . json_encode($allowedValues)
        );
    }
}
