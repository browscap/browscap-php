<?php
declare(strict_types = 1);

namespace BrowscapPHP\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception\FileNotFoundException;
use BrowscapPHP\IniParser\IniParser;
use Psr\Log\LoggerInterface;

/**
 * patternHelper to convert the ini data, parses the data and stores them into the cache
 */
interface ConverterInterface
{
    /**
     * Sets a filesystem instance
     *
     * @param Filesystem $file
     */
    public function setFilesystem(Filesystem $file) : void;

    /**
     * Returns a filesystem instance
     *
     * @return Filesystem
     */
    public function getFilesystem() : Filesystem;

    /**
     * converts a file
     *
     * @param string $iniFile
     */
    public function convertFile(string $iniFile) : void;

    /**
     * converts the string content
     *
     * @param string $iniString
     */
    public function convertString(string $iniString) : void;

    /**
     * Parses the ini data to get the version of loaded ini file
     *
     * @param string $iniString The loaded ini data
     *
     * @return int
     */
    public function getIniVersion(string $iniString) : int;

    /**
     * sets the version
     *
     * @param int $version
     * @return ConverterInterface
     */
    public function setVersion(int $version) : void;

    /**
     * stores the version of the ini file into cache
     */
    public function storeVersion() : void;
}
