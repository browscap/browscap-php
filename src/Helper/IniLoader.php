<?php
declare(strict_types = 1);

namespace BrowscapPHP\Helper;

/**
 * class to load the browscap.ini
 */
final class IniLoader implements IniLoaderInterface
{
    /**
     * The location from which download the ini file. The placeholder for the file should be represented by a %s.
     *
     * @var string
     */
    private $remoteIniUrl = 'http://browscap.org/stream?q=%q';

    /**
     * The location to use to check out if a new version of the browscap.ini file is available.
     * @var string
     */
    private $remoteTimeUrl = 'http://browscap.org/version';

    /**
     * @var string
     */
    private $remoteVersionUrl = 'http://browscap.org/version-number';

    /**
     * @var string
     */
    private $remoteFilename = IniLoaderInterface::PHP_INI;

    /**
     * sets the name of the local ini file
     *
     * @param string $name the file name
     *
     * @throws \BrowscapPHP\Helper\Exception
     */
    public function setRemoteFilename(string $name): void
    {
        if (empty($name)) {
            throw new Exception(
                'the filename can not be empty',
                Exception::INI_FILE_MISSING
            );
        }

        $this->remoteFilename = $name;
    }

    /**
     * returns the of the remote location for updating the ini file
     *
     * @return string
     */
    public function getRemoteIniUrl() : string
    {
        return str_replace('%q', $this->remoteFilename, $this->remoteIniUrl);
    }

    /**
     * returns the of the remote location for checking the version of the ini file
     *
     * @return string
     */
    public function getRemoteTimeUrl() : string
    {
        return $this->remoteTimeUrl;
    }

    /**
     * returns the of the remote location for checking the version of the ini file
     *
     * @return string
     */
    public function getRemoteVersionUrl() : string
    {
        return $this->remoteVersionUrl;
    }
}
