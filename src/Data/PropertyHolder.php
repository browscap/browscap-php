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
            'Comment',
            'Browser',
            'Browser_Maker',
            'Browser_Modus',
            'Platform',
            'Platform_Name',
            'Platform_Description',
            'Device_Name',
            'Platform_Maker',
            'Device_Code_Name',
            'Device_Maker',
            'Device_Brand_Name',
            'RenderingEngine_Name',
            'RenderingEngine_Description',
            'RenderingEngine_Maker',
            'Parent',
            'PropertyName',
            'CDF',
        ];

        if (in_array($propertyName, $stringProperties)) {
            return self::TYPE_STRING;
        }

        $arrayProperties = [
            'Browser_Type',
            'Device_Type',
            'Device_Pointing_Method',
            'Browser_Bits',
            'Platform_Bits',
        ];

        if (in_array($propertyName, $arrayProperties)) {
            return self::TYPE_IN_ARRAY;
        }

        $genericProperties = [
            'Platform_Version',
            'RenderingEngine_Version',
            'Released',
            'Format',
            'Type',
        ];

        if (in_array($propertyName, $genericProperties)) {
            return self::TYPE_GENERIC;
        }

        $numericProperties = [
            'Version',
            'CssVersion',
            'AolVersion',
            'MajorVer',
            'MinorVer',
        ];

        if (in_array($propertyName, $numericProperties)) {
            return self::TYPE_NUMBER;
        }

        $booleanProperties = [
            'Alpha',
            'Beta',
            'Win16',
            'Win32',
            'Win64',
            'Frames',
            'IFrames',
            'Tables',
            'Cookies',
            'BackgroundSounds',
            'JavaScript',
            'VBScript',
            'JavaApplets',
            'ActiveXControls',
            'isMobileDevice',
            'isTablet',
            'isSyndicationReader',
            'Crawler',
            'MasterParent',
            'LiteMode',
            'isFake',
            'isAnonymized',
            'isModified',
        ];

        if (in_array($propertyName, $booleanProperties)) {
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
                    'Useragent Anonymizer',
                    'Browser',
                    'Offline Browser',
                    'Multimedia Player',
                    'Library',
                    'Feed Reader',
                    'Email Client',
                    'Bot/Crawler',
                    'Application',
                    'Tool',
                    'unknown',
                ];
                break;
            case 'Device_Type':
                $allowedValues = [
                    'Console',
                    'TV Device',
                    'Tablet',
                    'Mobile Phone',
                    'Smartphone',    // actual mobile phone with touchscreen
                    'Feature Phone', // older mobile phone
                    'Mobile Device',
                    'FonePad',       // Tablet sized device with the capability to make phone calls
                    'Desktop',
                    'Ebook Reader',
                    'Car Entertainment System',
                    'Digital Camera',
                    'unknown',
                ];
                break;
            case 'Device_Pointing_Method':
                // This property is taken from http://www.scientiamobile.com/wurflCapability
                $allowedValues = [
                    'joystick', 'stylus', 'touchscreen', 'clickwheel', 'trackpad', 'trackball', 'mouse', 'unknown',
                ];
                break;
            case 'Browser_Bits':
            case 'Platform_Bits':
                $allowedValues = [
                    '0', '8', '16', '32', '64',
                ];
                break;
            default:
                throw new \InvalidArgumentException('Property "' . $property . '" is not defined to be validated');
                break;
        }

        if (in_array($value, $allowedValues)) {
            return $value;
        }

        throw new \InvalidArgumentException(
            'invalid value given for Property "' . $property . '": given value "' . (string) $value . '", allowed: '
            . json_encode($allowedValues)
        );
    }
}
