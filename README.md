Browser Capabilities PHP Project
================================

This is a userland replacement for PHP's native `get_browser()` function, which is _officially supported_ by the Browser Capabilities Project.

[![Build Status](https://secure.travis-ci.org/browscap/browscap-php.png?branch=master)](http://travis-ci.org/browsecap/browscap-php) [![Code Coverage](https://scrutinizer-ci.com/g/browscap/browscap-php/badges/coverage.png?s=61cb32ca83d2053ed9b140690b6e18dfa00e4639)](https://scrutinizer-ci.com/g/browscap/browscap-php/) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/browscap/browscap-php/badges/quality-score.png?s=db1cc1699b1cb6ac6ae46754ef9612217eba5526)](https://scrutinizer-ci.com/g/browscap/browscap-php/)

Installation
------------

Run the command below to install via Composer

```shell
composer require browscap/browscap-php
```

Then you may identify the current user agent like so:

```php
use phpbrowscap\Browscap;

$browscap = new Browscap();
$info = $browscap->getBrowser();
```

Recommended Setup
-----------------

a sample using composer with taking the useragent from the global $_SERVER variable

```php
require 'vendor/autoload.php';

// The Browscap class is in the phpbrowscap namespace, so import it
use phpbrowscap\Browscap;

// Create a new Browscap object (loads or creates the cache)
$bc = new Browscap('path/to/the/cache/dir');

// Get information about the current browser's user agent
$current_browser = $bc->getBrowser();
```

If you have an user agent you can change the function
```php
$current_browser = $bc->getBrowser($the_user_agent);
```

If you like arrays more than the StdClass you'll get it with this change
```php
$current_browser = $bc->getBrowser($the_user_agent, true);
```

It is highly recommended that you disable the auto update functionality, and create a background cron script to perform the update. This way, you do not make another request every time. So your usual usage would look like this:

```php
use phpbrowscap\Browscap;

$browscap = new Browscap($cacheDir);
$browscap->doAutoUpdate = false;
$info = $browscap->getBrowser();
```


If your projects needs more than one server, or you dont like file caches, you may use the Detector class instaed of the Browscap class.
```php
require 'vendor/autoload.php';

// The Browscap class is in the phpbrowscap namespace, so import it
use phpbrowscap\Detector;

// Create a new Browscap object (loads or creates the cache)
$bc = new Detector('path/to/the/cache/dir');

// Get information about the current browser's user agent
$current_browser = $bc->getBrowser();
```

If you have more than one process using that cache dir you can set a cache prefix.
```php
$bc = new Detector('path/to/the/cache/dir');
$bc->setCachePrefix('abc');
$current_browser = $bc->getBrowser();
```

If you want to log something that happens with the detector you may set an logger.
This logger has to implement the logger interface from Psr\Log\LoggerInterface
```php
$bc = new Detector('path/to/the/cache/dir');
$bc->setLogger($logger);
$current_browser = $bc->getBrowser();
```

And you could write a cron script such as this, to run once a day:

```php
use phpbrowscap\Browscap;

$browscap = new Browscap($cacheDir);
$browscap->updateCache();
```

Issues and feature requests
---------------------------

Please report your issues and ask for new features on the GitHub Issue Tracker
at https://github.com/browscap/browscap-php/issues

Please report incorrectly identified User Agents and browser detect in the browscap.ini
file here: https://github.com/browscap/browscap/issues
