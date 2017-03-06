<?php
/**
 * Copyright (c) 1998-2015 Browser Capabilities Project
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
 * @copyright  1998-2015 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace BrowscapPHP\Formatter;

/**
 * formatter to output the data like the native get_browser function
 *
 * @category   Browscap-PHP
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
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
    private $settings = [];

    /**
     * a list of possible properties
     *
     * @var array
     */
    private $defaultproperties = [
        'browser_name_regex' => null,
        'browser_name_pattern' => null,
        'Parent' => null,
        'Comment' => 'Default Browser',
        'Browser' => 'Default Browser',
        'Browser_Type' => 'unknown',
        'Browser_Bits' => '0',
        'Browser_Maker' => 'unknown',
        'Browser_Modus' => 'unknown',
        'Version' => '0.0',
        'MajorVer' => '0',
        'MinorVer' => '0',
        'Platform' => 'unknown',
        'Platform_Version' => 'unknown',
        'Platform_Description' => 'unknown',
        'Platform_Bits' => '0',
        'Platform_Maker' => 'unknown',
        'Alpha' => 'false',
        'Beta' => 'false',
        'Win16' => 'false',
        'Win32' => 'false',
        'Win64' => 'false',
        'Frames' => 'false',
        'IFrames' => 'false',
        'Tables' => 'false',
        'Cookies' => 'false',
        'BackgroundSounds' => 'false',
        'JavaScript' => 'false',
        'VBScript' => 'false',
        'JavaApplets' => 'false',
        'ActiveXControls' => 'false',
        'isMobileDevice' => 'false',
        'isTablet' => 'false',
        'isSyndicationReader' => 'false',
        'Crawler' => 'false',
        'isFake' => 'false',
        'isAnonymized' => 'false',
        'isModified' => 'false',
        'CssVersion' => '0',
        'AolVersion' => '0',
        'Device_Name' => 'unknown',
        'Device_Maker' => 'unknown',
        'Device_Type' => 'unknown',
        'Device_Pointing_Method' => 'unknown',
        'Device_Code_Name' => 'unknown',
        'Device_Brand_Name' => 'unknown',
        'RenderingEngine_Name' => 'unknown',
        'RenderingEngine_Version' => 'unknown',
        'RenderingEngine_Description' => 'unknown',
        'RenderingEngine_Maker' => 'unknown',
    ];

    /**
     * Sets the data (done by the parser)
     *
     * @param array $settings
     *
     * @return \BrowscapPHP\Formatter\PhpGetBrowser
     */
    public function setData(array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->settings[strtolower($key)] = $value;
        }

        return $this;
    }

    /**
     * Gets the data (in the preferred format)
     *
     * @return \stdClass
     */
    public function getData()
    {
        $output = new \stdClass();

        foreach (array_keys($this->defaultproperties) as $property) {
            $key = strtolower($property);

            if (array_key_exists($key, $this->settings)) {
                $output->$key = $this->settings[$key];
            } elseif ('parent' !== $key) {
                $output->$key = $this->defaultproperties[$property];
            }
        }

        // Don't want to normally do this, just if it exists in the data file
        // for our test runs
        if (array_key_exists('patternid', $this->settings)) {
            $output->patternid = $this->settings['patternid'];
        }

        return $output;
    }
}
