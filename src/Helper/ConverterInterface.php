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
     */
    public function setFilesystem(Filesystem $file): void;

    /**
     * converts a file
     *
     * @throws FileNotFoundException
     * @throws ErrorReadingFileException
     */
    public function convertFile(string $iniFile): void;

    /**
     * converts the string content
     *
     * @throws void
     */
    public function convertString(string $iniString): void;

    /**
     * Parses the ini data to get the version of loaded ini file
     *
     * @param string $iniString The loaded ini data
     *
     * @throws void
     */
    public function getIniVersion(string $iniString): int;

    /**
     * sets the version
     *
     * @throws void
     */
    public function setVersion(int $version): void;

    /**
     * stores the version of the ini file into cache
     *
     * @throws void
     */
    public function storeVersion(): void;
}
