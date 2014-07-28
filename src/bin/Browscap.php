<?php
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

$application = new Application('ua-parser');
$application->add(new ConvertCommand($resourceDirectory, $defaultIniFile));
$application->add(new UpdateCommand($resourceDirectory));
$application->add(new ParserCommand($resourceDirectory));
$application->add(new LogfileCommand());
$application->add(new FetchCommand($defaultIniFile));

$application->run();
