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
 * formatter for backwards compatibility with 2.x
 *
 * @category   Browscap-PHP
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class LegacyFormatter implements FormatterInterface
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
    private $settings = [];

    /**
     * LegacyFormatter constructor.
     *
     * @param array $options Formatter optioms
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Sets the data (done by the parser)
     *
     * @param array $settings
     *
     * @return \BrowscapPHP\Formatter\PhpGetBrowser
     */
    public function setData(array $settings)
    {
        $this->settings = $settings;

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

        foreach ($this->settings as $key => $property) {
            if ($this->options['lowercase']) {
                $key = strtolower($key);
            }

            $output->$key = $property;
        }

        return $output;
    }
}
