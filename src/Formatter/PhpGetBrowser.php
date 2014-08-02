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
 * @package    Formatter
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Formatter;

/**
 * formatter to output the data like the native get_browser function
 *
 * @category   Browscap-PHP
 * @package    Formatter
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class PhpGetBrowser implements FormatterInterface
{
    /**
     * Variable to save the settings in, type depends on implementation
     *
     * @var array
     */
    private $settings = array();

    /**
     * a list of possible properties
     *
     * @var array
     */
    private $defaultproperties = array(
        'browser_name_regex',
        'browser_name_pattern',
        'Parent',
        'Comment',
        'Browser',
        'Browser_Type',
        'Browser_Bits',
        'Browser_Maker',
        'Browser_Modus',
        'Version',
        'MajorVer',
        'MinorVer',
        'Platform',
        'Platform_Version',
        'Platform_Description',
        'Platform_Bits',
        'Platform_Maker',
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
        'CssVersion',
        'AolVersion',
        'Device_Name',
        'Device_Maker',
        'Device_Type',
        'Device_Pointing_Method',
        'Device_Code_Name',
        'Device_Brand_Name',
        'RenderingEngine_Name',
        'RenderingEngine_Version',
        'RenderingEngine_Description',
        'RenderingEngine_Maker',
    );

    public function __construct()
    {
        $this->settings = array();
    }

    /**
     * Sets the data (done by the parser)
     *
     * @param array $settings
     */
    public function setData(array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->settings[strtolower($key)] = $value;
        }
    }

    /**
     * Gets the data (in the preferred format)
     *
     * @return \stdClass
     */
    public function getData()
    {
        $output = new \stdClass();

        foreach ($this->defaultproperties as $property) {
            $key = strtolower($property);

            if (array_key_exists($key, $this->settings)) {
                $output->$key = $this->settings[$key];
            }
        }

        return $output;
    }
}
