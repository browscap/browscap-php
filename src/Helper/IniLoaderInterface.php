<?php

declare(strict_types=1);

namespace BrowscapPHP\Helper;

/**
 * class to load the browscap.ini
 */
interface IniLoaderInterface
{
    public const PHP_INI_LITE = 'Lite_PHP_BrowsCapINI';
    public const PHP_INI_FULL = 'Full_PHP_BrowsCapINI';
    public const PHP_INI      = 'PHP_BrowsCapINI';

    /**
     * sets the name of the local ini file
     *
     * @param string $name the file name
     *
     * @throws Exception
     *
     * @no-named-arguments
     */
    public function setRemoteFilename(string $name): void;

    /**
     * returns the of the remote location for updating the ini file
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function getRemoteIniUrl(): string;

    /**
     * returns the of the remote location for checking the version of the ini file
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function getRemoteTimeUrl(): string;

    /**
     * returns the of the remote location for checking the version of the ini file
     *
     * @throws void
     *
     * @no-named-arguments
     */
    public function getRemoteVersionUrl(): string;
}
