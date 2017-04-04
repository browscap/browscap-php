<?php
declare(strict_types = 1);

namespace BrowscapPHP\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;

/**
 * Exception to handle errors if one argument is required
 */
final class InvalidArgumentException extends BaseInvalidArgumentException
{
    public static function oneOfCommandArguments(string ...$requiredArguments) : self
    {
        return new self(
            sprintf('One of the command arguments "%s" is required', implode('", "', $requiredArguments))
        );
    }
}
