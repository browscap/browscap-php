<?php
declare(strict_types = 1);

namespace BrowscapPHP\Parser\Helper;

/**
 * includes general functions for the work with patterns
 */
final class SubKey
{
    private function __construct()
    {
    }

    /**
     * Gets the subkey for the pattern cache file, generated from the given string
     *
     * @param  string $string
     *
     * @return string
     */
    public static function getPatternCacheSubkey(string $string) : string
    {
        return $string[0] . $string[1];
    }

    /**
     * Gets all subkeys for the pattern cache files
     *
     * @return string[]
     */
    public static function getAllPatternCacheSubkeys() : array
    {
        $subkeys = [];
        $chars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];

        foreach ($chars as $charOne) {
            foreach ($chars as $charTwo) {
                $subkeys[$charOne . $charTwo] = '';
            }
        }

        return $subkeys;
    }

    /**
     * Gets the sub key for the ini parts cache file, generated from the given string
     *
     * @param  string $string
     *
     * @return string
     */
    public static function getIniPartCacheSubKey(string $string) : string
    {
        return $string[0] . $string[1] . $string[2];
    }

    /**
     * Gets all sub keys for the inipart cache files
     *
     * @return string[]
     */
    public static function getAllIniPartCacheSubKeys() : array
    {
        $subKeys = [];
        $chars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];

        foreach ($chars as $charOne) {
            foreach ($chars as $charTwo) {
                foreach ($chars as $charThree) {
                    $subKeys[] = $charOne . $charTwo . $charThree;
                }
            }
        }

        return $subKeys;
    }
}
