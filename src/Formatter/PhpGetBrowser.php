<?php
namespace phpbrowscap\Formatter;

/**
 * PhpGetBrowser formatter class
 *
 * This formatter modifies the basic data, so that you get the same result
 * as with the PHP get_browser() function (an object, and all keys lower case).
 *
 * @note There is on difference: The wrong encoded character used in
 * "browser_name_regex" of the standard PHP get_browser() result has been
 * replaced. The regular expression itself is the same.
 * @see https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=612364
 *
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Christoph Ziegenberg <christoph@ziegenberg.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @package phpbrowscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @copyright Copyright (c) 2014 Christoph Ziegenberg <christoph@ziegenberg.com>
 * @version 0.1
 * @license http://www.opensource.org/licenses/MIT MIT License
 * @link https://github.com/crossjoin/browscap
 */
class PhpGetBrowser implements FormatterInterface
{
    /**
     * Variable to save the settings in, type depends on implementation
     *
     * @var mixed
     */
    private $settings;

    public function __construct()
    {
        $this->settings = new \stdClass();
    }

    /**
     * Sets the data (done by the parser)
     *
     * @param array $settings
     */
    public function setData(array $settings)
    {
        foreach ($settings as $key => $value) {
            $key = strtolower($key);
            $this->settings->$key = $value;
        }
    }

    /**
     * Gets the data (in the preferred format)
     *
     * @return \stdClass
     */
    public function getData()
    {
        return $this->settings;
    }
}
