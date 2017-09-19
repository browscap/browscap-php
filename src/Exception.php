<?php
declare(strict_types = 1);

namespace BrowscapPHP;

/**
 * Browscap.ini parsing class base exception
 */
class Exception extends \Exception
{
    public const LOCAL_FILE_MISSING = 100;
    public const NO_RESULT_CLASS_RETURNED = 200;
    public const STRING_VALUE_EXPECTED = 300;
    public const CACHE_DIR_MISSING = 400;
    public const CACHE_DIR_INVALID = 401;
    public const CACHE_DIR_NOT_READABLE = 402;
    public const CACHE_DIR_NOT_WRITABLE = 403;
    public const CACHE_INCOMPATIBLE = 500;
    public const INVALID_DATETIME = 600;
    public const LOCAL_FILE_NOT_READABLE = 700;
    public const REMOTE_UPDATE_NOT_POSSIBLE = 800;
    public const INI_FILE_MISSING = 900;
}
