<?php
declare(strict_types = 1);

namespace BrowscapPHP\Formatter;

/**
 * formatter for backwards compatibility with 2.x
 */
final class LegacyFormatter implements FormatterInterface
{
    /**
     * Options for the formatter
     *
     * @var array
     */
    private $options = [];

    /**
     * Default formatter options
     *
     * @var array
     */
    private $defaultOptions = [
        'lowercase' => false,
    ];

    /**
     * Variable to save the settings in, type depends on implementation
     *
     * @var array
     */
    private $data = [];

    /**
     * LegacyFormatter constructor.
     *
     * @param array $options Formatter optioms
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Sets the data (done by the parser)
     *
     * @param array $settings
     */
    public function setData(array $settings) : void
    {
        $this->data = $settings;
    }

    /**
     * Gets the data (in the preferred format)
     *
     * @return \stdClass
     */
    public function getData() : \stdClass
    {
        $output = new \stdClass();

        foreach ($this->data as $key => $property) {
            if ($this->options['lowercase']) {
                $key = strtolower($key);
            }

            $output->{$key} = $property;
        }

        return $output;
    }
}
