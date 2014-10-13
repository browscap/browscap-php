Browser Capabilities PHP Project
================================

_Hacking around with PHP to have a better solution than `get_browser()`_

[![Build Status](https://secure.travis-ci.org/browscap/browscap-php.png?branch=master)](http://travis-ci.org/browsecap/browscap-php) [![Code Coverage](https://scrutinizer-ci.com/g/browscap/browscap-php/badges/coverage.png?s=61cb32ca83d2053ed9b140690b6e18dfa00e4639)](https://scrutinizer-ci.com/g/browscap/browscap-php/) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/browscap/browscap-php/badges/quality-score.png?s=db1cc1699b1cb6ac6ae46754ef9612217eba5526)](https://scrutinizer-ci.com/g/browscap/browscap-php/)


Changes
-------

Please see [changelog](CHANGELOG.md) for a list of recent changes.


Introduction
------------

The [browscap.ini](http://browscap.org/) file is a
database which provides a lot of details about browsers and their capabilities, such as name,
versions, Javascript support and so on.

PHP's native [get_browser()](http://php.net/get_browser) function parses this
file and provides you with a complete set of information about every browser's
details, But it requires the path to the browscap.ini file to be specified in
the php.ini [browscap](http://php.net/manual/en/ref.misc.php#ini.browscap)
directive which is flagged as `PHP_INI_SYSTEM`.

Since in most shared hosting environments you have not access to the php.ini
file, the browscap directive cannot be modified and you are stuck with either
and outdated database or without browscap support at all.

Browscap is a standalone class for PHP >=5.3 that gets around the limitations of
`get_browser()` and manages the whole thing.
It offers methods to update, cache, adapt and get details about every supplied
user agent on a standalone basis.
It's also much faster than `get_browser()` while still returning the same results.

If you can switch away from `get_browser()` then you definitely should. This implementation is very inferior
when compared to browscap-php. Not only the pure PHP implementation is way faster, especially with opcache,
as you can see here: https://github.com/browscap/browscap-php#features but also it is actually being updated,
in contrary to `get_browser()`.

So if there are some RAM, speed issues, or problems with ini parsing, they will most likely be fixed in browscap-php
and WON'T in `get_browser()`.

Browscap is a [Composer](https://packagist.org/packages/browscap/browscap-php) package.

The Browscap.ini database now has an official site at http://browscap.org/.

Quick start
-----------

Before you can start, you have to download the browscap.ini file and convert it into a cache. There are two ways.

1. Download the file and convert it in two steps. The downloaded file will be stored in a local file, but there is no check
   if the remote file has changed. If your cache gets corrupted you only need to rerun the `convert` command.

```php
php bin/Browscap.php browscap:fetch
php bin/Browscap.php browscap:convert
```

2. Download the file and convert it in one step. The downloaded file will not be stored in a local file, but there is a check
   if the remote file has changed. If your cache gets corrupted you have clean the cache and restart the process.

```php
php bin/Browscap.php browscap:update
```

A sample using composer with taking the useragent from the global $_SERVER variable.

```php
require 'vendor/autoload.php';

// The Browscap class is in the phpbrowscap namespace, so import it
use phpbrowscap\Browscap;

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
    ->update(\phpbrowscap\Helper\IniLoader::PHP_INI_FULL)
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

Features
--------

Here is a non-exhaustive feature list of the Browscap class:

 * Very fast
   * at least 3 times faster than get_browser() when not using opcache
   * **20 or more** times faster than get_browser() when using opcache ([see tests](https://github.com/quentin389/ua-speed-tests))
 * Standalone and fully PHP configuration independent (no need for php.ini setting)
 * Fully get_browser() compatible (with some get_browser() bugs  fixed)
 * User agent auto-detection
 * Returns object or array
 * Parsed .ini file cached directly into PHP arrays (leverages opcache)
 * Accepts any .ini file (even ASP and lite versions)
 * Auto updated browscap.ini file and cache from remote server with version checking
 * Fully configurable, including configurable remote update server and update schedules
 * `PHP >= 5.3` compatible
 * Released under the MIT License


Issues and feature requests
---------------------------

Please report your issues and ask for new features on the GitHub Issue Tracker
at https://github.com/browscap/browscap-php/issues

Please report incorrectly identified User Agents and browser detect in the browscap.ini
file here: https://github.com/browscap/browscap/issues
