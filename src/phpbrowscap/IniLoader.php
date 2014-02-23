<?php
namespace phpbrowscap;

use FileLoader\Loader;
use Psr\Log\LoggerInterface;

/**
 * class to load the browscap.ini
 *
 * PHP version 5
 *
 * Copyright (c) 2006-2012 Jonathan Stoppani
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
 * @package    Browscap
 * @author     Jonathan Stoppani <jonathan@stoppani.name>
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @author     Mikołaj Misiurewicz <quentin389+phpb@gmail.com>
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/GaretJax/phpbrowscap/
 */
class IniLoader
{
    /**
     * Current version of the class.
     */
    const VERSION = '2.0b';

    /**
     * Options for auto update capabilities
     *
     * $remoteVerUrl: The location to use to check out if a new version of the
     *                browscap.ini file is available.
     * $remoteIniUrl: The location from which download the ini file.
     *                The placeholder for the file should be represented by a %s.
     * $timeout: The timeout for the requests.
     * $updateInterval: The update interval in seconds.
     * $errorInterval: The next update interval in seconds in case of an error.
     * $doAutoUpdate: Flag to disable the automatic interval based update.
     * $updateMethod: The method to use to update the file, has to be a value of
     *                an UPDATE_* constant, null or false.
     *
     * The default source file type is changed from normal to full. The performance difference
     * is MINIMAL, so there is no reason to use the standard file whatsoever. Either go for light,
     * which is blazing fast, or get the full one. (note: light version doesn't work, a fix is on its way)
     */
    private $remoteIniUrl = 'http://tempdownloads.browserscap.com/stream.php?Full_PHP_BrowscapINI';
    private $remoteVerUrl = 'http://tempdownloads.browserscap.com/versions/version-date.php';
    private $timeout = 5;

    /**
     * Path to the cache directory
     *
     * @var string
     */
    private $cacheDir = null;

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
     * @var LoggerInterface
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
    private $iniFilename = null;

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param string $cacheDir
     *
     * @throws Exception
     */
    public function __construct($cacheDir)
    {
        if (!isset($cacheDir)) {
            throw new Exception(
                'You have to provide a path to read/store the browscap cache file',
                Exception::CACHE_DIR_MISSING
            );
        }

        $oldCacheDir = $cacheDir;
        $cacheDir    = realpath($cacheDir);

        if (false === $cacheDir) {
            throw new Exception(
                'The cache path "' . $oldCacheDir . '" is invalid. '
                . 'Are you sure that it exists and that you have permission '
                . 'to access it?',
                Exception::CACHE_DIR_INVALID
            );
        }

        // Is the cache dir really the directory or is it directly the file?
        if (is_file($cacheDir) && substr($cacheDir, -4) === '.php') {
            $this->cacheFilename = basename($cacheDir);
            $this->cacheDir      = dirname($cacheDir);
        } elseif (is_dir($cacheDir)) {
            $this->cacheDir = $cacheDir;
        } else {
            throw new Exception(
                'The cache path "' . $oldCacheDir . '" is invalid. '
                . 'Are you sure that it exists and that you have permission '
                . 'to access it?',
                Exception::CACHE_DIR_INVALID
            );
        }

        if (!is_readable($this->cacheDir)) {
            throw new Exception(
                'Its not possible to read from the given cache path "'
                . $oldCacheDir . '"',
                Exception::CACHE_DIR_NOT_READABLE
            );
        }

        if (!is_writable($this->cacheDir)) {
            throw new Exception(
                'Its not possible to write to the given cache path "'
                . $oldCacheDir . '"',
                Exception::CACHE_DIR_NOT_WRITABLE
            );
        }

        $this->cacheDir .= DIRECTORY_SEPARATOR;
    }

    /**
     * sets the logger
     *
     * @param LoggerInterface $logger
     *
     * @return IniLoader
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * sets the loader
     *
     * @param Loader $loader
     *
     * @return IniLoader
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
     * @throws Exception
     * @return IniLoader
     */
    public function setLocaleFile($filename)
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
     * @param string $ininame the file name
     *
     * @throws Exception
     * @return IniLoader
     */
    public function setIniFile($ininame)
    {
        if (empty($ininame)) {
            throw new Exception(
                'the filename can not be empty', Exception::INI_FILE_MISSING
            );
        }

        $this->iniFilename = $ininame;

        return $this;
    }

    /**
     * returns the of the remote location for updating the ini file
     *
     * @return string
     */
    public function getRemoteIniUrl()
    {
        $iniUrl = $this->remoteIniUrl;
        $prefix = 'http://tempdownloads.browserscap.com/stream.php?';

        switch ($this->iniFilename) {
            case 'lite_php_browscap.ini':
                $iniUrl = $prefix . 'Lite_PHP_BrowscapINI';
                break;
            case 'php_browscap.ini':
                $iniUrl = $prefix . 'PHP_BrowscapINI';
                break;
            case 'full_php_browscap.ini':
                $iniUrl = $prefix . 'Full_PHP_BrowscapINI';
                break;
            default:
                // do nothing here
                break;
        }

        return $iniUrl;
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
     * @return Loader
     */
    public function getLoader()
    {
        if (null === $this->loader) {
            $this->loader = new Loader($this->cacheDir);
        }

        if (null !== $this->localFile) {
            $this->loader->setLocaleFile($this->localFile);
            $this->loader->setCacheFile(basename($this->localFile));
        } else {
            $this->loader->setCacheFile($this->iniFilename);
        }

        return $this->loader;
    }

    /**
     * XXX save
     *
     * loads the ini file from a remote or local location and stores it into
     * the cache dir, parses the ini file
     *
     * @throws Exception
     * @return array the parsed ini file
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
        $browscap = $internalLoader->load();
        $browscap = explode("\n", $browscap);

        // quote the values for the data kyes Browser and Parent
        $pattern = Browscap::REGEX_DELIMITER
            . '('
            . Browscap::VALUES_TO_QUOTE
            . ')="?([^"]*)"?$'
            . Browscap::REGEX_DELIMITER;

        // Ok, lets read the file
        $content = '';
        foreach ($browscap as $subject) {
            $subject = trim($subject);
            $content .= preg_replace($pattern, '$1="$2"', $subject) . "\n";
        }

        /*
         * we have the ini content available as string
         * -> parse the string
         */
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $browsers = parse_ini_string($content, true, INI_SCANNER_RAW);
        } else {
            $browsers = parse_ini_string($content, true);
        }

        return $browsers;
    }
}
