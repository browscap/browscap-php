<?php

declare(strict_types=1);

namespace BrowscapPHP\Helper;

use BrowscapPHP\Exception\ErrorReadingFileException;
use BrowscapPHP\Exception\FileNotFoundException;

/**
 * patternHelper to convert the ini data, parses the data and stores them into the cache
 */
interface ConverterInterface
{
    /**
     * Sets a filesystem instance
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function setFilesystem(Filesystem $file): void;

    /**
     * converts a file
     *
     * @throws FileNotFoundException
     * @throws ErrorReadingFileException
     *
     * @no-named-arguments
     */
    public function convertFile(string $iniFile): void;

    /**
     * converts the string content
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function convertString(string $iniString): void;

    /**
     * Parses the ini data to get the version of loaded ini file
     *
     * @param string $iniString The loaded ini data
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function getIniVersion(string $iniString): int;

    /**
     * sets the version
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function setVersion(int $version): void;

    /**
     * stores the version of the ini file into cache
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function storeVersion(): void;
}
