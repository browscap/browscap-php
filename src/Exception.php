<?php
declare(strict_types = 1);

namespace BrowscapPHP;

/**
 * Browscap.ini parsing class base exception
 */
class Exception extends \Exception
{
    const LOCAL_FILE_MISSING = 100;
    const NO_RESULT_CLASS_RETURNED = 200;
    const STRING_VALUE_EXPECTED = 300;
    const CACHE_DIR_MISSING = 400;
    const CACHE_DIR_INVALID = 401;
    const CACHE_DIR_NOT_READABLE = 402;
    const CACHE_DIR_NOT_WRITABLE = 403;
    const CACHE_INCOMPATIBLE = 500;
    const INVALID_DATETIME = 600;
    const LOCAL_FILE_NOT_READABLE = 700;
    const REMOTE_UPDATE_NOT_POSSIBLE = 800;
    const INI_FILE_MISSING = 900;
}
