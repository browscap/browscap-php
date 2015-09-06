Browser Capabilities PHP Project
================================

This is a userland replacement for PHP's native `get_browser()` function, which is _officially supported_ by the Browser Capabilities Project.

Note that this is the new version 3 branch now. You can still see the version 2 branch [here](https://github.com/browscap/browscap-php/tree/2.x).

[![Build Status](https://secure.travis-ci.org/browscap/browscap-php.png?branch=master)](http://travis-ci.org/browsecap/browscap-php) [![Code Coverage](https://scrutinizer-ci.com/g/browscap/browscap-php/badges/coverage.png?s=61cb32ca83d2053ed9b140690b6e18dfa00e4639)](https://scrutinizer-ci.com/g/browscap/browscap-php/) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/browscap/browscap-php/badges/quality-score.png?s=db1cc1699b1cb6ac6ae46754ef9612217eba5526)](https://scrutinizer-ci.com/g/browscap/browscap-php/)

Installation
------------

Run the command below to install via Composer

```shell
composer require browscap/browscap-php
```

Then you may identify the current user agent like so:

```php
use BrowscapPHP\Browscap;

$browscap = new Browscap();
$info = $browscap->getBrowser();
```

Recommended Setup
-----------------

Before you can start, you have to download the browscap.ini file and convert it into a cache. There are two ways.

1. Download the file and convert it in two steps. The downloaded file will be stored in a local file, but there is no check
   if the remote file has changed. If your cache gets corrupted you only need to rerun the `convert` command.

```php
bin/browscap browscap:fetch
bin/browscap browscap:convert
```

2. Download the file and convert it in one step. The downloaded file will not be stored in a local file, but there is a check
   if the remote file has changed. If your cache gets corrupted you have clean the cache and restart the process.

```php
bin/browscap browscap:update
```

A sample using composer with taking the useragent from the global $_SERVER variable.

```php
require 'vendor/autoload.php';

// The Browscap class is in the BrowscapPHP namespace, so import it
use BrowscapPHP\Browscap;

// Create a new Browscap object (loads or creates the cache)
$bc = new Browscap();

// Get information about the current browser's user agent
$current_browser = $bc->getBrowser();
```

If you have an user agent you can change the function
```php
$current_browser = $bc->getBrowser($the_user_agent);
```

If you want to log something that happens with the detector you may set an logger.
This logger has to implement the logger interface from Psr\Log\LoggerInterface
```php
$bc = new Browscap();
$bc->setLogger($logger);
$current_browser = $bc->getBrowser();
```

If you want to use an other cache than the file cache, you may set a different one. You have to
change the cache adapter before building the cache with the `convert` or the `update` commands.

NOTE: If you want to use a different cache, the samples above will not work, because they are using
a predefined file cache

This cache adapter has to implement the adapter interface from WurflCache\Adapter\AdapterInterface
```php
$adapter = new \WurflCache\Adapter\Memcache(<your memcache configuration as array>);
$bc = new Browscap();
$bc->setCache($adapter);
$current_browser = $bc->getBrowser();
```

In this sample a memcache is used to store the full version of the bropwscap.ini file
```php
$adapter = new \WurflCache\Adapter\Memcache(<your memcache configuration as array>);

$bc = new Browscap();
$bc
    ->setCache($adapter)
    ->update(\BrowscapPHP\Helper\IniLoader::PHP_INI_FULL)
;
```

If you are behind a proxy you have to set a configuration with the proxy data. Parts who are not
needed for your connection (like the port if the standard port is used) dont need to be set

```php
$proxyConfig = array(
    'ProxyProtocol' => 'http',
    'ProxyHost'     => 'example.org',
    //'ProxyPort'     => null,
    'ProxyAuth'     => 'basic',
    'ProxyUser'     => 'your username',
    'ProxyPassword' => 'your super secret password',
);

$bc = new Browscap();
$bc->setOptions($proxyConfig);
```

Issues and feature requests
---------------------------

Please report your issues and ask for new features on the GitHub Issue Tracker
at https://github.com/browscap/browscap-php/issues

Please report incorrectly identified User Agents and browser detect in the browscap.ini
file here: https://github.com/browscap/browscap/issues
