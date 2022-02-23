Browser Capabilities PHP Project
================================

This is a userland replacement for PHP's native `get_browser()` function, which is _officially supported_ by the Browser Capabilities Project.

[![CI](https://github.com/browscap/browscap-php/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/browscap/browscap-php/actions/workflows/continuous-integration.yml)

Installation
------------

Run the command below to install via Composer

```shell
composer require browscap/browscap-php 
```

Then you may identify the current user agent this way:

```php
$cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache($doctrineFileCache); // or maybe any other PSR-16 compatible caches
$logger = new \Monolog\Logger('name'); // or maybe any other PSR-3 compatible logger

$browscap = new \BrowscapPHP\Browscap($cache, $logger);
$info = $browscap->getBrowser();
```

Recommended Setup
-----------------

Before you can start, you have to run the `browscap:fetch` command to download the `browscap.ini` file, and use the
`browscap:convert` command to convert it into a cache. There are two ways.

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

BC breaks in version 7.0.x
-----------------------------

```
 - [BC] BrowscapPHP\Command\UpdateCommand was marked "@internal"
 - [BC] BrowscapPHP\Command\FetchCommand was marked "@internal"
 - [BC] BrowscapPHP\Command\ConvertCommand was marked "@internal"
 - [BC] BrowscapPHP\Command\CheckUpdateCommand was marked "@internal"
 - [BC] BrowscapPHP\Command\ParserCommand was marked "@internal"
 - [BC] BrowscapPHP\Helper\Filesystem was marked "@internal"
```

Setup Examples
--------------

```php
$fileCache = new \League\Flysystem\Local\LocalFilesystemAdapter($cacheDir);
$filesystem = new \League\Flysystem\Filesystem($fileCache);
$cache = new \MatthiasMullie\Scrapbook\Psr16\SimpleCache(
    new \MatthiasMullie\Scrapbook\Adapters\Flysystem($filesystem)
);

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
