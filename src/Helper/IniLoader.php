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
 * @package    Helper
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Helper;

use FileLoader\Exception as LoaderException;
use FileLoader\Loader;
use Psr\Log\LoggerInterface;

/**
 * class to load the browscap.ini
 *
 * @category   Browscap-PHP
 * @package    Helper
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
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
     * $remoteVerUrl: The location to use to check out if a new version of the
     *                browscap.ini file is available.
     * $remoteIniUrl: The location from which download the ini file.
     *                The placeholder for the file should be represented by a %s.
     * $timeout:      The timeout for the requests.
     */
    private $remoteIniUrl = 'http://browscap.org/stream?q=%q';
    private $remoteVerUrl = 'http://browscap.org/version';
    private $timeout = 5;

    /**
     * The path of the local version of the browscap.ini file from which to
     * update (to be set only if used).
     *
     * @var string
     */
    private $localFile = null;

    /**
     * an logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * an file loader instance
     *
     * @var \FileLoader\Loader
     */
    private $loader = null;

    /**
     * @var string
     */
    private $remoteFilename = self::PHP_INI;

    /**
     * Options for the updater. The array should be overwritten,
     * containing all options as keys, set to the default value.
     *
     * @var array
     */
    private $options = array();

    /**
     * sets the logger
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \phpbrowscap\Helper\IniLoader
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * returns the logger
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * sets the loader
     *
     * @param \FileLoader\Loader $loader
     *
     * @return \phpbrowscap\Helper\IniLoader
     */
    public function setLoader(Loader $loader)
    {
        $this->loader = $loader;

        return $this;
    }

    /**
     * creates the ini loader
     *
     * @return \FileLoader\Loader
     */
    public function getLoader()
    {
        if (null === $this->loader) {
            $this->loader = new Loader();
        }

        if (null !== $this->localFile) {
            $this->loader->setLocalFile($this->localFile);
        }

        $this->loader->setOptions($this->options);

        return $this->loader;
    }

    /**
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws \phpbrowscap\Helper\Exception
     * @return \phpbrowscap\Helper\IniLoader
     */
    public function setLocalFile($filename = null)
    {
        if (empty($filename)) {
            throw new Exception(
                'the filename can not be empty',
                Exception::LOCAL_FILE_MISSING
            );
        }

        $this->localFile = $filename;

        return $this;
    }

    /**
     * sets the name of the local ini file
     *
     * @param string $name the file name
     *
     * @throws \phpbrowscap\Helper\Exception
     * @return \phpbrowscap\Helper\IniLoader
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
    public function getRemoteVerUrl()
    {
        return $this->remoteVerUrl;
    }

    /**
     * returns the timeout
     *
     * @return integer
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Sets multiple loader options at once
     *
     * @param array $options
     *
     * @return IniLoader
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * XXX save
     *
     * loads the ini file from a remote or local location and returns the content of the file
     *
     * @throws \phpbrowscap\Helper\Exception
     * @return string                        the content of the loaded ini file
     */
    public function load()
    {
        $internalLoader = $this->getLoader();
        $internalLoader
            ->setRemoteDataUrl($this->getRemoteIniUrl())
            ->setRemoteVerUrl($this->getRemoteVerUrl())
            ->setTimeout($this->getTimeout())
        ;

        try {
            // Get updated .ini file
            return $internalLoader->load();
        } catch (LoaderException $exception) {
            throw new Exception('could not load the data file', 0, $exception);
        }
    }

    /**
     * Gets the remote file update timestamp
     *
     * @throws \phpbrowscap\Helper\Exception
     * @return integer                       the remote modification timestamp
     */
    public function getMTime()
    {
        $internalLoader = $this->getLoader();
        $internalLoader
            ->setRemoteDataUrl($this->getRemoteIniUrl())
            ->setRemoteVerUrl($this->getRemoteVerUrl())
            ->setTimeout($this->getTimeout())
        ;

        try {
            // Get updated timestamp
            return $internalLoader->getMTime();
        } catch (LoaderException $exception) {
            throw new Exception('could not load the new version', 0, $exception);
        }
    }
}
