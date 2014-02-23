<?php
namespace phpbrowscap;

    /**
     * PHP version 5.3
     *
     * LICENSE:
     *
     * Copyright (c) 2013, Thomas Mueller <t_mueller_stolzenhain@yahoo.de>
     *
     * All rights reserved.
     *
     * Redistribution and use in source and binary forms, with or without
     * modification, are permitted provided that the following conditions are met:
     *
     * * Redistributions of source code must retain the above copyright notice,
     *   this list of conditions and the following disclaimer.
     * * Redistributions in binary form must reproduce the above copyright notice,
     *   this list of conditions and the following disclaimer in the documentation
     *   and/or other materials provided with the distribution.
     * * Neither the name of the authors nor the names of its contributors may be
     *   used to endorse or promote products derived from this software without
     *   specific prior written permission.
     *
     * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
     * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
     * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
     * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
     * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
     * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
     * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
     * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
     * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
     * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
     * POSSIBILITY OF SUCH DAMAGE.
     *
     * @package   BrowserDetector
     * @author    Thomas Mueller <t_mueller_stolzenhain@yahoo.de>
     * @version   Stable 2.1.3 $Date: 2010/09/18 15:43:21
     * @license   http://opensource.org/licenses/BSD-3-Clause New BSD License
     */
/**
 * Provides static supporting functions
 *
 * @package   BrowserDetector
 *
 */
class Support
{
    /**
     * @var array
     */
    private $source = array();

    /**
     * The HTTP Headers that this application will look through to find the best
     * User Agent, if one is not specified
     *
     * @var Array
     */
    private $userAgentHeaders
        = array(
            'HTTP_X_DEVICE_USER_AGENT',
            'HTTP_X_ORIGINAL_USER_AGENT',
            'HTTP_X_OPERAMINI_PHONE_UA',
            'HTTP_X_SKYFIRE_PHONE',
            'HTTP_X_BOLT_PHONE_UA',
            'HTTP_USER_AGENT'
        );

    /**
     * @param array|null $source
     */
    public function __construct($source = null)
    {
        if (is_null($source) || !is_array($source)) {
            $source = array();
        }

        $this->source = $source;
    }

    /**
     * detect the useragent
     *
     * @return string
     */
    public function getUserAgent()
    {
        $userAgent = '';

        foreach ($this->userAgentHeaders as $header) {
            if (array_key_exists($header, $this->source)
                && $this->source[$header]
            ) {
                $userAgent = $this->cleanParam($this->source[$header]);
                break;
            }
        }

        return $userAgent;
    }

    /**
     * clean Parameters taken from GET or POST Variables
     *
     * @param string $param the value to be cleaned
     *
     * @return string
     */
    private function cleanParam($param)
    {
        return strip_tags(trim(urldecode($param)));
    }
}
