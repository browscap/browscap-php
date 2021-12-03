<?php

declare(strict_types=1);

namespace BrowscapPHP\Formatter;

use stdClass;

use function array_key_exists;
use function array_merge;
use function strtolower;

/**
 * formatter for backwards compatibility with 2.x
 */
final class LegacyFormatter implements FormatterInterface
{
    /**
     * Options for the formatter
     *
     * @var bool[]
     * @phpstan-var array{lowercase?: bool}
     */
    private array $options = [];

    /**
     * Default formatter options
     *
     * @var bool[]
     * @phpstanvar array{lowercase: bool}
     */
    private array $defaultOptions = ['lowercase' => false];

    /**
     * Variable to save the settings in, type depends on implementation
     *
     * @var string[]|bool[]|null[]
     */
    private array $data = [];

    /**
     * @param bool[] $options Formatter options
     * @phpstan-param array{lowercase?: bool} $options
     *
     * @throws void
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Sets the data (done by the parser)
     *
     * @param string[]|bool[]|null[] $settings
     *
     * @throws void
     */
    public function setData(array $settings): void
    {
        $this->data = $settings;
    }

    /**
     * Gets the data (in the preferred format)
     *
     * @throws void
     */
    public function getData(): stdClass
    {
        $output = new stdClass();

        foreach ($this->data as $key => $property) {
            if (array_key_exists('lowercase', $this->options) && $this->options['lowercase']) {
                $key = strtolower($key);
            }

            $output->{$key} = $property;
        }

        return $output;
    }
}
