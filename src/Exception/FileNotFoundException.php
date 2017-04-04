<?php
declare(strict_types = 1);

namespace BrowscapPHP\Exception;

use Exception;

/**
 * Exception to handle errors because a file does not exist
 */
final class FileNotFoundException extends Exception
{
    /**
     * @param string $file
     *
     * @return FileNotFoundException
     */
    public static function fileNotFound(string $file) : self
    {
        return new self(sprintf('File "%s" does not exist', $file));
    }
}
