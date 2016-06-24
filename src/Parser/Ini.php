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

namespace BrowscapPHP\Parser;

use BrowscapPHP\Formatter\FormatterInterface;
use BrowscapPHP\Parser\Helper\GetDataInterface;
use BrowscapPHP\Parser\Helper\GetPatternInterface;

/**
 * Ini parser class (compatible with PHP 5.3+)
 *
 * @category   Browscap-PHP
 * @author     Christoph Ziegenberg <christoph@ziegenberg.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class Ini implements ParserInterface
{
    /**
     * @var Helper\GetPatternInterface
     */
    private $patternHelper = null;

    /**
     * @var Helper\GetDataInterface
     */
    private $dataHelper = null;

    /**
     * Formatter to use
     *
     * @var \BrowscapPHP\Formatter\FormatterInterface
     */
    private $formatter = null;

    /**
     * class constructor
     *
     * @param \BrowscapPHP\Parser\Helper\GetPatternInterface $patternHelper
     * @param \BrowscapPHP\Parser\Helper\GetDataInterface    $dataHelper
     * @param \BrowscapPHP\Formatter\FormatterInterface      $formatter
     */
    public function __construct(
        GetPatternInterface $patternHelper,
        GetDataInterface $dataHelper,
        FormatterInterface $formatter
    ) {
        $this->patternHelper = $patternHelper;
        $this->dataHelper    = $dataHelper;
        $this->formatter     = $formatter;
    }

    /**
     * Gets the browser data formatr for the given user agent
     * (or null if no data avaailble, no even the default browser)
     *
     * @param  string                  $userAgent
     * @return FormatterInterface|null
     */
    public function getBrowser($userAgent)
    {
        $userAgent = strtolower($userAgent);
        $formatter = null;

        foreach ($this->patternHelper->getPatterns($userAgent) as $patterns) {
            $patternToMatch = '/^(?:' . str_replace("\t", ')|(?:', $patterns) . ')$/i';

            if (!preg_match($patternToMatch, $userAgent)) {
                continue;
            }

            // strtok() requires less memory than explode()
            $pattern = strtok($patterns, "\t");

            while ($pattern !== false) {
                $pattern       = str_replace('[\d]', '(\d)', $pattern);
                $quotedPattern = '/^' . $pattern . '$/i';
                $matches       = [];

                if (preg_match($quotedPattern, $userAgent, $matches)) {
                    // Insert the digits back into the pattern, so that we can search the settings for it
                    if (count($matches) > 1) {
                        array_shift($matches);
                        foreach ($matches as $oneMatch) {
                            $numPos  = strpos($pattern, '(\d)');
                            $pattern = substr_replace($pattern, $oneMatch, $numPos, 4);
                        }
                    }

                    // Try to get settings - as digits have been replaced to speed up the pattern search (up to 90 faster),
                    // we won't always find the data in the first step - so check if settings have been found and if not,
                    // search for the next pattern.
                    $settings = $this->dataHelper->getSettings($pattern);

                    if (count($settings) > 0) {
                        $formatter = $this->formatter;
                        $formatter->setData($settings);
                        break 2;
                    }
                }

                $pattern = strtok("\t");
            }
        }

        return $formatter;
    }
}
