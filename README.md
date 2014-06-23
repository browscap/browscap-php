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

The [browscap.ini](http://browscap.org/), which
provides a lot of details about browsers and their capabilities, such as name,
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

A quick start guide is available on the GitHub wiki at the original repostitory for this project, at the following address:
https://github.com/GaretJax/phpbrowscap/wiki/QuickStart (the Wiki is on the original project page)


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
