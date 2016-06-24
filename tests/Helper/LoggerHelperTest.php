<?php
/**
 * Copyright (c) 1998-2015 Browser Capabilities Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Refer to the LICENSE file distributed with this package.
 *
 * @category   BrowscapTest
 * @copyright  1998-2015 Browser Capabilities Project
 * @license    MIT
 */

namespace BrowscapPHPTest\Helper;

use BrowscapPHP\Helper\LoggerHelper;

/**
 * Class LoggerHelperTest
 *
 * @category   BrowscapTest
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 */
class LoggerHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $helper = new LoggerHelper();
        self::assertInstanceOf('\Monolog\Logger', $helper->create());
    }

    public function testCreateInDebugMode()
    {
        $helper = new LoggerHelper();
        self::assertInstanceOf('\Monolog\Logger', $helper->create(true));
    }
}
