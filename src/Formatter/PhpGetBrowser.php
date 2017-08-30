<?php
declare(strict_types = 1);

namespace BrowscapPHP\Formatter;

/**
 * Formatter to output the data like the native get_browser function
 */
final class PhpGetBrowser implements FormatterInterface
{
    /**
     * Variable to save the settings in, type depends on implementation
     *
     * @var array
     */
    private $data = [];

    /**
     * a list of possible properties
     *
     * @var array
     */
    private $defaultProperties = [
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
        'Alpha' => false,
        'Beta' => false,
        'Win16' => false,
        'Win32' => false,
        'Win64' => false,
        'Frames' => false,
        'IFrames' => false,
        'Tables' => false,
        'Cookies' => false,
        'BackgroundSounds' => false,
        'JavaScript' => false,
        'VBScript' => false,
        'JavaApplets' => false,
        'ActiveXControls' => false,
        'isMobileDevice' => false,
        'isTablet' => false,
        'isSyndicationReader' => false,
        'Crawler' => false,
        'isFake' => false,
        'isAnonymized' => false,
        'isModified' => false,
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
     */
    public function setData(array $settings) : void
    {
        foreach ($settings as $key => $value) {
            $this->data[strtolower($key)] = $value;
        }
    }

    /**
     * Gets the data (in the preferred format)
     *
     * @return \stdClass
     */
    public function getData() : \stdClass
    {
        $output = new \stdClass();

        $propertyNames = array_keys($this->defaultProperties);
        foreach ($propertyNames as $property) {
            $key = strtolower($property);

            if (array_key_exists($key, $this->data)) {
                $output->{$key} = $this->data[$key];
            } elseif ('parent' !== $key) {
                $output->{$key} = $this->defaultProperties[$property];
            }
        }

        // Don't want to normally do this, just if it exists in the data file
        // for our test runs
        if (array_key_exists('patternid', $this->data)) {
            $output->patternid = $this->data['patternid'];
        }

        return $output;
    }
}
