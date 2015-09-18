Browser Capabilities PHP Project
================================

This is a userland replacement for PHP's native `get_browser()` function, which is _officially supported_ by the Browser Capabilities Project.

Forked from https://github.com/GaretJax/phpbrowscap.

[![Build Status](https://secure.travis-ci.org/browscap/browscap-php.png?branch=2.x)](http://travis-ci.org/browsecap/browscap-php) [![Code Coverage](https://scrutinizer-ci.com/g/browscap/browscap-php/badges/coverage.png?s=61cb32ca83d2053ed9b140690b6e18dfa00e4639)](https://scrutinizer-ci.com/g/browscap/browscap-php/) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/browscap/browscap-php/badges/quality-score.png?s=db1cc1699b1cb6ac6ae46754ef9612217eba5526)](https://scrutinizer-ci.com/g/browscap/browscap-php/)

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

It is highly recommended that you disable the auto update functionality, and create a background cron script to perform the update. This way, you do not make another request every time. So your usual usage would look like this:

```php
use phpbrowscap\Browscap;

$browscap = new Browscap($cacheDir);
$browscap->doAutoUpdate = false;
$info = $browscap->getBrowser();
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
