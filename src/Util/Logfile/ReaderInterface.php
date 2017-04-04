<?php
declare(strict_types=1);

namespace BrowscapPHP\Util\Logfile;

/**
 * interface for all readers
 */
interface ReaderInterface
{
    /**
     * @param  string $line
     * @return bool
     */
    public function test(string $line) : bool;

    /**
     * @param  string $line
     * @return string
     */
    public function read(string $line) : string;
}
