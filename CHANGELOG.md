Changelog
-------

### Version 2.0.0 - June 23, 2014
- #25 for caching error
- #14 reducing memory consumption slightly
- #22 use the "normal" version instead of "full" version
- #15 general improvements
- #13 code formatting improvements
- #12 allow proxy settings via fsockopen with context
- #9 default timeout settings fix
- #11, #27 improved tests

### Version 2.0.0-beta - August 25, 2013
- Added a new method to retrieve the source file version `$browscap->getSourceVersion()`.
- Added a safety feature to regenerate the cache file always when `Browscap::CACHE_FILE_VERSION` changes.
- Updated source file download URLs to new temporary URLs.
- Added new lines `\n` to the cache files for readability.
- Default download URL is changed so it will get and parse the full file instead of 'standard'.
  [ua-speed-tests](https://github.com/quentin389/ua-speed-tests) shows that there is only a small performance difference between
  using those two versions.
- Performance upgrades (see [ua-speed-tests](https://github.com/quentin389/ua-speed-tests) for performance tests):
  * **5 times faster** for real user agents, with opcache on
  * **11 times faster** for user agents that do not match anything, with opcache on
  * **3 times faster** for real user agents, without using opcache
  * **5 times faster** for user agents that do not match anything, without using opcache
  * Regular expression pattern matches are being grouped by version numbers. The matches are performed
    in two stages. 1 - standard regular expression match with numbers that differ across source file
    patterns replaced with single character wildcard match. 2 - a check is performed on found numeric
    values to see if any of the grouped values are an exact match. If not, then the searching process resumes.
    This is the main source of the speed optimization. It greatly reduces the source file size and
    greatly increases matching performance.
  * Data that is not required to perform matches or return results was removed from the cache files.
    That includes the source file match strings, which can be recreated from the regex ones, and a large set
    of browser name entries which were never used because they had parents. Decreasing the cache file size
    is very important for when you don't use any PHP opcache, because loading large data structures into
    PHP takes a very long time.
  * Arrays that are not used in `foreach` loops were serialized in the cache file. This also decreases the time
    it takes to load the cache file when not using opcache. It's generally a very bad idea to load large arrays
    with subarrays into PHP. Serializing does a great job when optimizing performance. 
  * The above changes address performance issues brought up in https://github.com/GaretJax/phpbrowscap/issues/26
- Bug fixes:
  * Fixed https://github.com/GaretJax/phpbrowscap/issues/35
  * Fixed https://github.com/GaretJax/phpbrowscap/issues/34
  * Fixed https://github.com/GaretJax/phpbrowscap/issues/33
  * Fixed https://github.com/GaretJax/phpbrowscap/issues/32
  * Bug https://github.com/GaretJax/phpbrowscap/issues/17 is resolved, although that was fixed even before.
  * Merged https://github.com/GaretJax/phpbrowscap/pull/25 - those are mainly comment changes but there are also two fixes
    for `$browscap->clearProxySettings()` method, which did not work properly when an optional `$wrapper` parameter was passed. 
- Added a new testing class that compares result of `Browscap` to `get_browser()` for as many browsers as possible
  and checks if there are any differences in parsing. It also compares the parsing speed (in a simplistic way,
  more advanced tests are available at https://github.com/quentin389/ua-speed-tests).

### Version 1.0.0 - July 22, 2012
- Initial version
