<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
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
 * @package    Data
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Data;

/**
 * Class PropertyHolder
 *
 * @category   Browscap
 * @package    Data
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
     * Get the type of a property
     *
     * @param string $propertyName
     * @throws \Exception
     * @return string
     */
    public function getPropertyType($propertyName)
    {
        $stringProperties = array(
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
        );

        if (in_array($propertyName, $stringProperties)) {
            return self::TYPE_STRING;
        }

        $arrayProperties = array(
            'Browser_Type',
            'Device_Type',
            'Device_Pointing_Method',
            'Browser_Bits',
            'Platform_Bits',
        );

        if (in_array($propertyName, $arrayProperties)) {
            return self::TYPE_IN_ARRAY;
        }

        $genericProperties = array(
            'Platform_Version',
            'RenderingEngine_Version',
            'Released',
            'Format',
            'Type',
        );

        if (in_array($propertyName, $genericProperties)) {
            return self::TYPE_GENERIC;
        }

        $numericProperties = array(
            'Version',
            'CssVersion',
            'AolVersion',
            'MajorVer',
            'MinorVer',
        );

        if (in_array($propertyName, $numericProperties)) {
            return self::TYPE_NUMBER;
        }

        $booleanProperties = array(
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
        );

        if (in_array($propertyName, $booleanProperties)) {
            return self::TYPE_BOOLEAN;
        }

        throw new \InvalidArgumentException("Property {$propertyName} did not have a defined property type");
    }

    /**
     * Determine if the specified property is an "extra" property (that should
     * be included in the "full" versions of the files)
     *
     * @param string $propertyName
     * @param \Browscap\Writer\WriterInterface $writer
     * @return boolean
     */
    public function isExtraProperty($propertyName, \Browscap\Writer\WriterInterface $writer = null)
    {
        if (null !== $writer && in_array($writer->getType(), array('csv', 'xml'))) {
            $additionalProperties = array('PropertyName', 'MasterParent', 'LiteMode');

            if (in_array($propertyName, $additionalProperties)) {
                return false;
            }
        }

        $extraProperties = array(
            'Browser_Type',
            'Browser_Bits',
            'Browser_Maker',
            'Browser_Modus',
            'Platform_Name',
            'Platform_Bits',
            'Platform_Maker',
            'Device_Code_Name',
            'Device_Brand_Name',
            'Device_Name',
            'Device_Maker',
            'Device_Type',
            'Device_Pointing_Method',
            'Platform_Description',
            'RenderingEngine_Name',
            'RenderingEngine_Version',
            'RenderingEngine_Description',
            'RenderingEngine_Maker',
        );

        if (in_array($propertyName, $extraProperties)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the specified property is an "extra" property (that should
     * be included in the "full" versions of the files)
     *
     * @param string $propertyName
     * @param \Browscap\Writer\WriterInterface $writer
     * @return boolean
     */
    public function isOutputProperty($propertyName, \Browscap\Writer\WriterInterface $writer = null)
    {
        $outputProperties = array(
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
            'Browser_Type',
            'Device_Type',
            'Device_Pointing_Method',
            'Browser_Bits',
            'Platform_Bits',
            'Platform_Version',
            'RenderingEngine_Version',
            'Version',
            'CssVersion',
            'AolVersion',
            'MajorVer',
            'MinorVer',
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
        );

        if (in_array($propertyName, $outputProperties)) {
            return true;
        }

        if (null !== $writer && in_array($writer->getType(), array('csv', 'xml'))) {
            $additionalProperties = array('PropertyName', 'MasterParent', 'LiteMode');

            if (in_array($propertyName, $additionalProperties)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $property
     * @param string $value
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function checkValueInArray($property, $value)
    {
        switch ($property) {
            case 'Browser_Type':
                $allowedValues = array(
                    'Useragent Anonymizer',
                    'Browser',
                    'Offline Browser',
                    'Multimedia Player',
                    'Library',
                    'Feed Reader',
                    'Email Client',
                    'Bot/Crawler',
                    'Application',
                    'unknown',
                );
                break;
            case 'Device_Type':
                $allowedValues = array(
                    'Console',
                    'TV Device',
                    'Tablet',
                    'Mobile Phone',
                    'Mobile Device',
                    'FonePad', // Tablet sized device with the capability to make phone calls
                    'Desktop',
                    'Ebook Reader',
                    'Car Entertainment System',
                    'unknown',
                );
                break;
            case 'Device_Pointing_Method':
                // This property is taken from http://www.scientiamobile.com/wurflCapability
                $allowedValues = array(
                    'joystick', 'stylus', 'touchscreen', 'clickwheel', 'trackpad', 'trackball', 'mouse', 'unknown'
                );
                break;
            case 'Browser_Bits':
            case 'Platform_Bits':
                $allowedValues = array(
                    '0', '8', '16', '32', '64'
                );
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
