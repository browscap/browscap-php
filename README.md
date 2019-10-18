Browser Capabilities PHP Project
================================

This is a userland replacement for PHP's native `get_browser()` function, which is _officially supported_ by the Browser Capabilities Project.

**Note that you are currently viewing the 4.x series. If you're looking for the unsupported 2.x version, please read the documentation for that branch [here](https://github.com/browscap/browscap-php/tree/2.x).**

[![Build Status](https://secure.travis-ci.org/browscap/browscap-php.png?branch=master)](http://travis-ci.org/browscap/browscap-php) [![codecov](https://codecov.io/gh/browscap/browscap-php/branch/master/graph/badge.svg)](https://codecov.io/gh/browscap/browscap-php)

Installation
------------

Run the command below to install via Composer

```shell
composer require browscap/browscap-php 
```

Then you may identify the current user agent this way:

```php
$cache = new \Roave\DoctrineSimpleCache\SimpleCacheAdapter($doctrineFileCache); // or maybe any other PSR-16 compatible caches
$logger = new \Monolog\Logger('name'); // or maybe any other PSR-3 compatible logger

$browscap = new \BrowscapPHP\Browscap($cache, $logger);
$info = $browscap->getBrowser();
```

Recommended Setup
-----------------

Before you can start, you have to download the browscap.ini file and convert it into a cache. There are two ways.

a. Download the file and convert it in two steps. The downloaded file will be stored in a local file, but there is no check
   if the remote file has changed. If your cache gets corrupted you only need to rerun the `convert` command.

```php
vendor/bin/browscap-php browscap:fetch
vendor/bin/browscap-php browscap:convert
```

b. Download the file and convert it in one step. The downloaded file will not be stored in a local file, but there is a check
   if the remote file has changed. If your cache gets corrupted you have clean the cache and restart the process.

```php
vendor/bin/browscap-php browscap:update
```
    
If you want to autoupdate the used cache, we recommend a separate cron job that calls the command listed above.

What's changed in version 4.x
-----------------------------

## BC breaks listed

 * Strict type hints have been added throughout. This may break some type assumptions made in earlier versions.
 * `CheckUpdateCommand`, `ConvertCommand`, `LogfileCommand`, `ParserCommand`, `UpdateCommand` removed `setCache` methods
   so that caches must now be constructor-injected
 * Many classes are now `final` - use composition instead of inheritance
 * `PropertyFormatter` now assumes any non-truthy values are `false`
 * `checkUpdate` method now throws an exception if we could not determine the "remote" version, or if there is no
   version in cache already.
 * `log` method was removed
   
## Changes

* for caching the [Doctrine Cache](https://github.com/doctrine/cache) package is used
  * Any other Cache compatible with [PSR-16](https://github.com/php-fig/simple-cache) could be used
* instead of the `debug` flag for the CLI commands the verbose flag has to be used


What's changed in version 3.x
-----------------------------

## Changes

* the namespace was changed to `BrowscapPHP` 
* the `Browscap` class was split into pieces
  * for caching the [WurflCache](https://github.com/mimmi20/WurflCache) package is used
  * for downloading the browscap.ini the [Guzzle HTTP](https://github.com/guzzle/guzzle) package is used

## Removed features

* the autoupdate function was removed
* all public properties were removed

## New features

* now it is possible to use other caches than the file cache (see the [WurflCache](https://github.com/mimmi20/WurflCache) package formore information)
* now it is possible to write your own formatter to change the output format 
* now it is possbile to set a PSR-3 compatible logger

Setup Examples
--------------

## Update your setup to version 4.x

This is the base setup in version 3.x.
```php
$bc = new \BrowscapPHP\Browscap();

$adapter = new \WurflCache\Adapter\File([\WurflCache\Adapter\File::DIR => $cacheDir]);
$bc->setCache($adapter);

$logger = new \Monolog\Logger('name');
$bc->setLogger($logger);

$result = $bc->getBrowser();
```

Change this to the base setup for version 4.x. to use the current cache directory
```php
$fileCache = new \Doctrine\Common\Cache\FilesystemCache($cacheDir);
$cache = new \Roave\DoctrineSimpleCache\SimpleCacheAdapter($fileCache);

$logger = new \Monolog\Logger('name');

$bc = new \BrowscapPHP\Browscap($cache, $logger);
$result = $bc->getBrowser();
```

NOTE: You may use any other cache which implements the [PSR-16](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-16-simple-cache.md) interface.

## Using the full browscap.ini file

```php
$bc = new \BrowscapPHP\BrowscapUpdater();
$bc->update(\BrowscapPHP\Helper\IniLoaderInterface::PHP_INI_FULL);
```

## Setting up a proxy configuration

If you are behind a proxy or need a spcific configuration, you have to set up a client instance. 
See into the [Guzzle documentation](http://docs.guzzlephp.org/en/latest/) for more information about this.

```php
$proxyConfig = [
    'proxy' => [
        'http'  => 'tcp://localhost:8125',
        'https' => 'tcp://localhost:8124',
    ],
];
$client = new \GuzzleHttp\Client($proxyConfig);
$bcu = new BrowscapUpdater();
$bcu->setClient($client);
```

Usage Examples
--------------

## Taking the user agent from the global $_SERVER variable

```php
$bc = new \BrowscapPHP\Browscap();
$current_browser = $bc->getBrowser();
```

## Using a sample useragent 

```php
$bc = new \BrowscapPHP\Browscap($cache, $logger);
$current_browser = $bc->getBrowser($the_user_agent);
```

the CLI commands
----------------

NOTE: If you don't want to use a file cache, you could not use the CLI commands. It is not possible to use other caches there at the moment.
NOTE: Each operation (fetch, update, check-update) which fetches data from the remote host browscap.org may run into the 
rate limit of that site. If this happens an Exception is thrown.

Each CLI command returns `zero` if everything went fine.

## check-update

If you only want to check if a new version of the browscap.ini is available, you can use the `check-update` command.

```php
vendor/bin/browscap-php browscap:check-update
```

### options

- `cache` (optional) the relative path to your cache directory

### return codes

- 1: no cached version found
- 2: no new version availble
- 3: an error occured while checking the cached version
- 4: an error occured while fetching the remote version
- 5: an other error occured 

## fetch

The `fetch` command downloads an ini file from browscap.org. 

```php
vendor/bin/browscap-php browscap:fetch
```

### options

- `cache` (optional) the relative path to your cache directory
- `remote-file` (optional) only required if you dont want to download the standerd file, possible values are
  - `PHP_BrowscapINI` downloads the standard file (default)
  - `Lite_PHP_BrowscapINI` downloads the lite file
  - `Full_PHP_BrowscapINI` downloads the full file
- `file` (optional) the relative path to the local file where the remote content is stored

### return codes

- 3: an error occured while checking the cached version
- 9: an error occured while fetching the remote data
- 10: an other error occured 

## convert

The `convert` command reads a local stored browscap.ini file and writes the contents into a cache. 

```php
vendor/bin/browscap-php browscap:convert
```

### options

- `file` (optional) the relative path to the local file where the remote content is stored, this should be the same file as in the fetch command
- `cache` (optional) the relative path to your cache directory

### return codes

- 6: the name of the file to convert is missing
- 7: the file to convert is not available or not readable
- 8: an other error occured while reading the file

## update

The `update` command downloads an ini file from browscap.org and writes the contents into a cache. No local files are created.

```php
vendor/bin/browscap-php browscap:update
```

### options

- `remote-file`(optional) only required if you dont want to download the standerd file, possible values are
  - `PHP_BrowscapINI` downloads the standard file (default)
  - `Lite_PHP_BrowscapINI` downloads the lite file
  - `Full_PHP_BrowscapINI` downloads the full file
- `cache` (optional) the relative path to your cache directory

### return codes

- 3: an error occured while checking the cached version
- 9: an error occured while fetching the remote data
- 10: an other error occured 

## parse

The `parse` command parses a given user agent and writes the result to the console.

```php
vendor/bin/browscap-php browscap:parse
```

### options

- `user-agent` (required) the user agent which should be parsed
- `cache` (optional) the relative path to your cache directory

### return codes

- 11: an other error occured while parsing the useragent

CLI Examples
------------

## Updating the cache using the full browscap.ini file

Note: Both ways to create/update the cache will use the `standard` mode file as default. 
If you want more detailed information you may change this with the `remote-file` option.
Please use the help function this parameter.

```php
vendor/bin/browscap-php browscap:update --remote-file Full_PHP_BrowscapINI
```

## Updating a custom cache dir

Each operation expect fetch uses a cache inside the `resources` directory inside the project. If you update this library with 
composer, the cache is cleared also. If you want to avoid this and want to set your own cache folder, 
you can use the `cache` option. If you do this, you have to set a Cache Instance for this this path.

```php
vendor/bin/browscap-php browscap:update --cache ./browscap-cache
```

Issues and feature requests
---------------------------

Please report your issues and ask for new features on the GitHub Issue Tracker
at https://github.com/browscap/browscap-php/issues

Please report incorrectly identified User Agents and browser detect in the browscap.ini
file here: https://github.com/browscap/browscap/issues
