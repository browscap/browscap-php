Browser Capabilities PHP Project
================================

This is a userland replacement for PHP's native `get_browser()` function, which is _officially supported_ by the Browser Capabilities Project.

**Note that you are currently viewing the unstable master branch (will be 3.x versions). If you're installing via `composer require browscap/browscap-php`, depending on your minimum stability, you may be using the latest version 2.x, so please read the documentation for that branch [here](https://github.com/browscap/browscap-php/tree/2.x).**

[![Build Status](https://secure.travis-ci.org/browscap/browscap-php.png?branch=master)](http://travis-ci.org/browscap/browscap-php) [![Code Coverage](https://scrutinizer-ci.com/g/browscap/browscap-php/badges/coverage.png?s=61cb32ca83d2053ed9b140690b6e18dfa00e4639)](https://scrutinizer-ci.com/g/browscap/browscap-php/) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/browscap/browscap-php/badges/quality-score.png?s=db1cc1699b1cb6ac6ae46754ef9612217eba5526)](https://scrutinizer-ci.com/g/browscap/browscap-php/)

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

What's changed in version 3.x
-----------------------------

##Changes

* the namespace was changed to `BrowscapPHP` 
* the `Browscap` class was splitted into pieces
  * for caching the [WurflCache](https://github.com/mimmi20/WurflCache) package is used
  * for downloading the browscap.ini the [FileLoader](https://github.com/mimmi20/FileLoader) package is used

##removed features

* the autoupdate function was removed
* all public properties were removed

##new features

* now it is possible to use other caches than the file cache
* now it is possible to write your own formatter to change the output format 
* now it is possbile to set a logger

Setup Examples
--------------

##update your setup to version 3.x

This is the base setup in version 2.x.
```php
// setup in version 2.x
$bc = new \phpbrowscap\Browscap($cacheDir);
```

Change this to the base setup for version 3.x.
```php
$bc      = new \BrowscapPHP\Browscap();
$adapter = new \WurflCache\Adapter\File(array(\WurflCache\Adapter\File::Dir => $cacheDir));
$bc->setCache($adapter)
```

##setting up a logger

If you want to log something that happens with the detector you may set an logger.
This logger has to implement the logger interface from [Psr\Log](https://github.com/php-fig/log)

```php
$bc     = new \BrowscapPHP\Browscap();
$logger = new \Monolog\Logger('name');
$bc->setLogger($logger);
```

##setting up a Memechache

This cache adapter has to implement the adapter interface from WurflCache\Adapter\AdapterInterface
```php
$memcacheConfiguration = array(
    'host'             => '127.0.0.1', // optional, defaults to '127.0.0.1'
    'port'             => 11211,       // optional, defaults to 11211
    'namespace'        => 'wurfl',     // optional, defaults to 'wurfl'
    'cacheExpiration'  => 0,           // optional, defaults to 0 (cache does not expire), expiration time in seconds
    'cacheVersion'     => '1234',      // optional, default value may change in external library
);
$adapter = new \WurflCache\Adapter\Memcached($memcacheConfiguration);
$bc = new \BrowscapPHP\Browscap();
$bc->setCache($adapter);
```

NOTE: Please look into the [WurflCache](https://github.com/mimmi20/WurflCache) package for infomation about the other cache adapters.

##using the full browscap.ini file

```php
$bc = new \BrowscapPHP\Browscap();
$bc->update(\BrowscapPHP\Helper\IniLoader::PHP_INI_FULL);
```

##setting up a proxy configuration

If you are behind a proxy you have to set a configuration with the proxy data. Parts who are not
needed for your connection (like the port if the standard port is used) dont need to be set

```php
$proxyConfig = array(
    'ProxyProtocol' => 'http',          // optional, defaults to `http`
    'ProxyHost'     => 'example.org', 
    'ProxyPort'     => 80,              // optional, defaults to port 80 or 443 (depends on the protocoll) 
    'ProxyAuth'     => 'basic',         // optional, only `basic` is available at the moment
    'ProxyUser'     => 'your username',
    'ProxyPassword' => 'your super secret password',
);
$bc = new \BrowscapPHP\Browscap();
$bc->setOptions($proxyConfig);
```

Usage Examples
--------------

##taking the user agent from the global $_SERVER variable

```php
$bc = new \BrowscapPHP\Browscap();
$current_browser = $bc->getBrowser();
```

##using a sample useragent 

```php
$bc = new \BrowscapPHP\Browscap();
$current_browser = $bc->getBrowser($the_user_agent);
```

the CLI commands
----------------

In version 3 some CLI commands were added.

NOTE: If you don't want to use a file cache, you could not use the CLI commands. It is not possible to use other caches there at the moment.
NOTE: Each operation (fetch, update, check-update) which fetches data from the remote host browscap.org may run into the 
rate limit of that site. If this happens an Exception is thrown.

##check-update

If you only want to check if a new version of the browscap.ini is available, you can use the `check-update` command.

```php
vendor/bin/browscap-php browscap:check-update
```

###options

- `debug` (optional) if set more messages are printed to the console
- `cache` (optional) the relative path to your cache directory

##fetch

The `fetch` command downloads an ini file from browscap.org. 

```php
vendor/bin/browscap-php browscap:fetch
```

###options

- `debug` (optional) if set more messages are printed to the console
- `remote-file` (optional) only required if you dont want to download the standerd file, possible values are
  - `PHP_BrowscapINI` downloads the standard file (default)
  - `Lite_PHP_BrowscapINI` downloads the lite file
  - `Full_PHP_BrowscapINI` downloads the full file
- `file` (optional) the relative path to the local file where the remote content is stored

##convert

The `convert` command reads a local stored browscap.ini file and writes the contents into a cache. 

```php
vendor/bin/browscap-php browscap:convert
```

###options

- `file` (optional) the relative path to the local file where the remote content is stored, this should be the same file as in the fetch command
- `debug` (optional) if set more messages are printed to the console
- `cache` (optional) the relative path to your cache directory

##update

The `update` command downloads an ini file from browscap.org and writes the contents into a cache. No local files are created.

```php
vendor/bin/browscap-php browscap:update
```

###options

- `debug` (optional) if set more messages are printed to the console
- `remote-file`(optional) only required if you dont want to download the standerd file, possible values are
  - `PHP_BrowscapINI` downloads the standard file (default)
  - `Lite_PHP_BrowscapINI` downloads the lite file
  - `Full_PHP_BrowscapINI` downloads the full file
- `cache` (optional) the relative path to your cache directory

##parse

The `parse` command parses a given user agent and writes the result to the console.

```php
vendor/bin/browscap-php browscap:parse
```

###options

- `user-agent` (required) the user agent which should be parsed
- `debug` (optional) if set more messages are printed to the console
- `cache` (optional) the relative path to your cache directory

##log

The `log` command parses a single access log file or a directory with access log files and writes the results into an output file. 

```php
vendor/bin/browscap-php browscap:log
```

###options

- `output` (required) the path to a log file where the results are stored
- `debug` (optional) if set more messages are printed to the console
- `cache` (optional) the relative path to your cache directory
- `log-file` (optional) the relative path to an access log file
- `log-dir` (optional) the relative path to directory with the log files
- `include` (optional) a glob compatible list of files which should be included, only used in comination with the `log-dir` option
- `exclude` (optional) a glob compatible list of files which should be excluded from parsing, only used in comination with the `log-dir` option

NOTE: One of both options `log-file` and `log-dir` is required.
NOTE: At the moment only Apache access logs are supported.

CLI Examples
------------

##updating the cache using the full browscap.ini file

Note: Both ways to create/update the cache and also checking the update will use the `standard` mode file as default. 
If you want more detailed information you may change this with the `remote-file` option.
Please use the help function this parameter.

```php
vendor/bin/browscap-php browscap:update --remote-file Full_PHP_BrowscapINI
```

##updating a custom cache dir

Each operation expect fetch uses a cache inside the `resources` directory inside the project. If you update this library with 
composer, the cache is cleared also. If you want to avoid this and want to set your own cache folder, 
you can use the `cache` option. If you do this, you have to set a Cache Instance for this this path (see below).

```php
vendor/bin/browscap-php browscap:update --cache ./browscap-cache
```

Issues and feature requests
---------------------------

Please report your issues and ask for new features on the GitHub Issue Tracker
at https://github.com/browscap/browscap-php/issues

Please report incorrectly identified User Agents and browser detect in the browscap.ini
file here: https://github.com/browscap/browscap/issues
