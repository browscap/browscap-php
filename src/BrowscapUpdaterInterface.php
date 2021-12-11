<?php

declare(strict_types=1);

namespace BrowscapPHP;

use BrowscapPHP\Exception\ErrorCachedVersionException;
use BrowscapPHP\Exception\ErrorReadingFileException;
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Exception\FileNameMissingException;
use BrowscapPHP\Exception\FileNotFoundException;
use BrowscapPHP\Exception\NoCachedVersionException;
use BrowscapPHP\Exception\NoNewVersionException;
use BrowscapPHP\Helper\Exception;
use BrowscapPHP\Helper\IniLoaderInterface;
use UnexpectedValueException;

/**
 * Browscap.ini parsing class with caching and update capabilities
 */
interface BrowscapUpdaterInterface
{
    /**
     * reads and parses an ini file and writes the results into the cache
     *
     * @throws FileNameMissingException
     * @throws FileNotFoundException
     * @throws ErrorReadingFileException
     * @throws UnexpectedValueException
     *
     * @no-named-arguments
     */
    public function convertFile(string $iniFile): void;

    /**
     * reads and parses an ini string and writes the results into the cache
     *
     * @throws UnexpectedValueException
     *
     * @no-named-arguments
     */
    public function convertString(string $iniString): void;

    /**
     * fetches a remote file and stores it into a local folder
     *
     * @param string $file       The name of the file where to store the remote content
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws FetcherException
     * @throws Exception
     * @throws ErrorCachedVersionException
     *
     * @no-named-arguments
     */
    public function fetch(string $file, string $remoteFile = IniLoaderInterface::PHP_INI): void;

    /**
     * fetches a remote file, parses it and writes the result into the cache
     *
     * if the local stored information are in the same version as the remote data no actions are
     * taken
     *
     * @param string $remoteFile The code for the remote file to load
     *
     * @throws FetcherException
     * @throws Exception
     * @throws ErrorCachedVersionException
     * @throws UnexpectedValueException
     *
     * @no-named-arguments
     */
    public function update(string $remoteFile = IniLoaderInterface::PHP_INI): void;

    /**
     * checks if an update on a remote location for the local file or the cache
     *
     * @return int|null The actual cached version if a newer version is available, null otherwise
     *
     * @throws FetcherException
     * @throws NoCachedVersionException
     * @throws ErrorCachedVersionException
     * @throws NoNewVersionException
     *
     * @no-named-arguments
     */
    public function checkUpdate(): ?int;
}
