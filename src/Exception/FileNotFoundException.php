<?php

declare(strict_types=1);

namespace BrowscapPHP\Exception;

use Exception;

use function sprintf;

/**
 * Exception to handle errors because a file does not exist
 */
final class FileNotFoundException extends Exception
{
    /** @return FileNotFoundException */
    public static function fileNotFound(string $file): self
    {
        return new self(sprintf('File "%s" does not exist', $file));
    }
}
