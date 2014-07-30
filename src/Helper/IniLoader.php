<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Refer to the LICENSE file distributed with this package.
 *
 * @category   Browscap-PHP
 * @package    Helper
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Helper;

use FileLoader\Loader;
use phpbrowscap\Browscap;
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
    /**
     * Current version of the class.
     */
    const VERSION = '3.0';
    
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
     * @var Loader
     */
    private $loader = null;

    /**
     * @var string
     */
    private $remoteFilename = self::PHP_INI;

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
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws \phpbrowscap\Helper\Exception
     * @return \phpbrowscap\Helper\IniLoader
     */
    public function setLocalFile($filename)
    {
        if (empty($filename)) {
            throw new Exception(
                'the filename can not be empty', Exception::LOCAL_FILE_MISSING
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
    public function setRemoteFilename($name)
    {
        if (empty($name)) {
            throw new Exception(
                'the filename can not be empty', Exception::INI_FILE_MISSING
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

        return $this->loader;
    }

    /**
     * XXX save
     *
     * loads the ini file from a remote or local location and returns the content of the file
     *
     * @throws \phpbrowscap\Helper\Exception
     * @return string the content of the loaded ini file
     */
    public function load()
    {
        $internalLoader = $this->getLoader();
        $internalLoader
            ->setRemoteDataUrl($this->getRemoteIniUrl())
            ->setRemoteVerUrl($this->getRemoteVerUrl())
            ->setTimeout($this->getTimeout());

        if (null !== $this->logger) {
            $internalLoader->setLogger($this->logger);
        }

        // Get updated .ini file
        return $internalLoader->load();
    }
}
