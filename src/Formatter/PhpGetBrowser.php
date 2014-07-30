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
 * @package    Formatter
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Formatter;

/**
 * formatter to output the data like the native get_browser function
 *
 * @category   Browscap-PHP
 * @package    Formatter
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class PhpGetBrowser implements FormatterInterface
{
    /**
     * Variable to save the settings in, type depends on implementation
     *
     * @var mixed
     */
    private $settings = null;

    public function __construct()
    {
        $this->settings = new \stdClass();
    }

    /**
     * Sets the data (done by the parser)
     *
     * @param array $settings
     */
    public function setData(array $settings)
    {
        foreach ($settings as $key => $value) {
            $key = strtolower($key);
            $this->settings->$key = $value;
        }
    }

    /**
     * Gets the data (in the preferred format)
     *
     * @return \stdClass
     */
    public function getData()
    {
        return $this->settings;
    }
}
