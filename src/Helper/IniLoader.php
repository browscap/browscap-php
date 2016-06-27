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

namespace BrowscapPHP\Helper;

/**
 * class to load the browscap.ini
 *
 * @category   Browscap-PHP
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class IniLoader
{
    const PHP_INI_LITE = 'Lite_PHP_BrowscapINI';
    const PHP_INI_FULL = 'Full_PHP_BrowscapINI';
    const PHP_INI      = 'PHP_BrowscapINI';

    /**
     * Options for update capabilities
     *
     * $remoteTimeUrl: The location to use to check out if a new version of the
     *                browscap.ini file is available.
     * $remoteIniUrl: The location from which download the ini file.
     *                The placeholder for the file should be represented by a %s.
     * $timeout:      The timeout for the requests.
     */
    private $remoteIniUrl     = 'http://browscap.org/stream?q=%q';
    private $remoteTimeUrl    = 'http://browscap.org/version';
    private $remoteVersionUrl = 'http://browscap.org/version-number';

    /**
     * @var string
     */
    private $remoteFilename = self::PHP_INI;

    /**
     * sets the name of the local ini file
     *
     * @param string $name the file name
     *
     * @throws \BrowscapPHP\Helper\Exception
     * @return \BrowscapPHP\Helper\IniLoader
     */
    public function setRemoteFilename($name = null)
    {
        if (empty($name)) {
            throw new Exception(
                'the filename can not be empty',
                Exception::INI_FILE_MISSING
            );
        }

        $this->remoteFilename = $name;

        return $this;
    }

    /**
     * returns the of the remote location for updating the ini file
     *
     * @return string
     */
    public function getRemoteIniUrl()
    {
        return str_replace('%q', $this->remoteFilename, $this->remoteIniUrl);
    }

    /**
     * returns the of the remote location for checking the version of the ini file
     *
     * @return string
     */
    public function getRemoteTimeUrl()
    {
        return $this->remoteTimeUrl;
    }

    /**
     * returns the of the remote location for checking the version of the ini file
     *
     * @return string
     */
    public function getRemoteVersionUrl()
    {
        return $this->remoteVersionUrl;
    }
}
