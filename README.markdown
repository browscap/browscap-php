Browser Capabilities PHP Project
================================

_Hacking around with PHP to have a better solution than `get_browser()`_

[![Build Status](https://secure.travis-ci.org/browscap/browscap-php.png?branch=master)](http://travis-ci.org/browsecap/browscap-php)


Changes (new version - 2.0)
-------

Please see [changelog](CHANGELOG.md) for a list of recent changes. (huge performance improvements!)


Introduction
------------

The [browscap.ini](http://tempdownloads.browserscap.com/) file is a
database which provides a lot of details about browsers and their capabilities, such as name,
versions, Javascript support and so on.

It was maintained by [Gary Keith](https://github.com/GaryKeith) and is 
now maintained by [RAD Moose](https://github.com/radmoose).
More information about the transfer of owners can be found here: https://groups.google.com/forum/#!topic/browscap/pk_dkkqdXzg
The [browscap.ini](http://tempdownloads.browserscap.com/), which
provides a lot of details about browsers and their capabilities, such as name,
versions, Javascript support and so on.

_Please note: [browscap.ini](http://tempdownloads.browserscap.com/) was maintained by [Gary Keith](https://github.com/GaryKeith) and is 
now maintained by [RAD Moose](https://github.com/radmoose). More information about the transfer of owners can be found [here](https://groups.google.com/forum/#!topic/browscap/pk_dkkqdXzg).
Browscap.ini source files are currently available at a temporary location (http://tempdownloads.browserscap.com/).
All the links in `Browscap` class are updated, but if you use custom links remember to change them!_

PHP's native [get_browser()](http://php.net/get_browser) function parses this
file and provides you with a complete set of information about every browser's
details, But it requires the path to the browscap.ini file to be specified in
the php.ini [browscap](http://ch2.php.net/manual/en/ref.misc.php#ini.browscap)
directive which is flagged as `PHP_INI_SYSTEM`.

Since in most shared hosting environments you have not access to the php.ini
file, the browscap directive cannot be modified and you are stuck with either
and outdated database or without browscap support at all.

Browscap is a standalone class for PHP >=5.3 that gets around the limitations of
`get_browser()` and manages the whole thing.
It offers methods to update, cache, adapt and get details about every supplied
user agent on a standalone basis.
It's also much faster than `get_browser()` while still returning the same results.

Browscap is a [Composer](https://packagist.org/packages/browscap/browscap-php) package.


Browscap.ini database is currently running on a temporary site (http://tempdownloads.browserscap.com/)
while some things are sorted out and redone after a change of owners. For more
information, look here: https://groups.google.com/d/msg/browscap/pk_dkkqdXzg/5ij0kxjCfocJ 

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
file on Google Groups here: https://groups.google.com/forum/#!forum/browscap

Please note that the Browscap class only parses and queries the browscap.ini
database provided by RAD Moose (previously by Gary Keith). If a browser is wrongly identified or a results
presents erroneous properties, please refer directly to the temporary browscap project
homepage at: http://tempdownloads.browserscap.com/ or post your misidentified browser and User Agent at
the Browscap Google Groups page: https://groups.google.com/forum/#!forum/browscap
