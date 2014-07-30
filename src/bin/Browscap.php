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
 * @package    Command
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Command;

use phpbrowscap\Exception;
use Symfony\Component\Console\Application;

chdir(dirname(dirname(__DIR__)));

$autoloadPaths = array(
    'vendor/autoload.php',
    '../../autoload.php',
);

$foundVendorAutoload = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require $path;
        $foundVendorAutoload = true;
        break;
    }
}

if (!$foundVendorAutoload) {
    throw new Exception('Could not find autoload path in any of the searched locations');
}

$resourceDirectory = 'resources/';
$defaultIniFile    = 'resources/browscap.ini';

$application = new Application('browscap.php');
$application->add(new ConvertCommand($resourceDirectory, $defaultIniFile));
$application->add(new UpdateCommand($resourceDirectory));
$application->add(new ParserCommand($resourceDirectory));
$application->add(new LogfileCommand());
$application->add(new FetchCommand($defaultIniFile));

ini_set('memory_limit', '256M');

$application->run();
