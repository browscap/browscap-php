<?php
declare(strict_types = 1);

namespace BrowscapPHP;

use BrowscapPHP\Helper\IniLoaderInterface;

/**
 * Browscap.ini parsing class with caching and update capabilities
 */
interface BrowscapUpdaterInterface
{
    /**
     * reads and parses an ini file and writes the results into the cache
     *
     * @param string $iniFile
     *
     * @throws \BrowscapPHP\Exception
     */
    public function convertFile(string $iniFile) : void;

    /**
     * reads and parses an ini string and writes the results into the cache
     *
     * @param string $iniString
     */
    public function convertString(string $iniString) : void;

    /**
     * fetches a remote file and stores it into a local folder
     *
     * @param string $file The name of the file where to store the remote content
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws \BrowscapPHP\Exception\FetcherException
     * @throws \BrowscapPHP\Helper\Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetch(string $file, string $remoteFile = IniLoaderInterface::PHP_INI) : void;

    /**
     * fetches a remote file, parses it and writes the result into the cache
     *
     * if the local stored information are in the same version as the remote data no actions are
     * taken
     *
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws \BrowscapPHP\Exception\FileNotFoundException
     * @throws \BrowscapPHP\Helper\Exception
     * @throws \BrowscapPHP\Exception\FetcherException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function update(string $remoteFile = IniLoaderInterface::PHP_INI) : void;

    /**
     * checks if an update on a remote location for the local file or the cache
     *
     * @throws \BrowscapPHP\Helper\Exception
     * @throws \BrowscapPHP\Exception\FetcherException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return int|null The actual cached version if a newer version is available, null otherwise
     */
    public function checkUpdate() : ?int;
}
