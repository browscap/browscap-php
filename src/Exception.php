<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Refer to the LICENSE file distributed with this package.
 *
 * @category   Browscap-PHP
 * @package    Browscap
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap;

/**
 * Browscap.ini parsing class base exception
 *
 * @category   Browscap-PHP
 * @package    Browscap
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class Exception extends \Exception
{
    const LOCAL_FILE_MISSING         = 100;
    const NO_RESULT_CLASS_RETURNED   = 200;
    const STRING_VALUE_EXPECTED      = 300;
    const CACHE_DIR_MISSING          = 400;
    const CACHE_DIR_INVALID          = 401;
    const CACHE_DIR_NOT_READABLE     = 402;
    const CACHE_DIR_NOT_WRITABLE     = 403;
    const CACHE_INCOMPATIBLE         = 500;
    const INVALID_DATETIME           = 600;
    const LOCAL_FILE_NOT_READABLE    = 700;
    const REMOTE_UPDATE_NOT_POSSIBLE = 800;
    const INI_FILE_MISSING           = 900;
}
