<?php
declare(strict_types = 1);

namespace BrowscapPHP\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Data\PropertyFormatter;
use BrowscapPHP\Data\PropertyHolder;
use BrowscapPHP\Helper\QuoterInterface;
use ExceptionalJSON\DecodeErrorException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * extracts the data and the data for theses pattern from the ini content, optimized for PHP 5.5+
 */
final class GetData implements GetDataInterface
{
    /**
     * The cache instance
     *
     * @var \BrowscapPHP\Cache\BrowscapCacheInterface
     */
    private $cache;

    /**
     * a logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \BrowscapPHP\Helper\QuoterInterface
     */
    private $quoter;

    /**
     * class contsructor
     *
     * @param \BrowscapPHP\Cache\BrowscapCacheInterface $cache
     * @param \Psr\Log\LoggerInterface                  $logger
     * @param \BrowscapPHP\Helper\QuoterInterface       $quoter
     */
    public function __construct(BrowscapCacheInterface $cache, LoggerInterface $logger, QuoterInterface $quoter)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->quoter = $quoter;
    }

    /**
     * Gets the settings for a given pattern (method calls itself to
     * get the data from the parent patterns)
     *
     * @param  string $pattern
     * @param  array  $settings
     *
     * @throws \UnexpectedValueException
     *
     * @return array
     */
    public function getSettings(string $pattern, array $settings = []) : array
    {
        // The pattern has been pre-quoted on generation to speed up the pattern search,
        // but for this check we need the unquoted version
        $unquotedPattern = $this->quoter->pregUnQuote($pattern);

        // Try to get settings for the pattern
        $addedSettings = $this->getIniPart($unquotedPattern);

        // set some additional data
        if (0 === count($settings)) {
            // The optimization with replaced digits get can now result in setting searches, for which we
            // won't find a result - so only add the pattern information, is settings have been found.
            //
            // If not an empty array will be returned and the calling function can easily check if a pattern
            // has been found.
            if (0 < count($addedSettings)) {
                $settings['browser_name_regex'] = '/^' . $pattern . '$/';
                $settings['browser_name_pattern'] = $unquotedPattern;
            }
        }

        // check if parent pattern set, only keep the first one
        $parentPattern = null;

        if (isset($addedSettings['Parent'])) {
            $parentPattern = $addedSettings['Parent'];

            if (isset($settings['Parent'])) {
                unset($addedSettings['Parent']);
            }
        }

        // merge settings
        $settings += $addedSettings;

        if (is_string($parentPattern)) {
            return $this->getSettings($this->quoter->pregQuote($parentPattern), $settings);
        }

        return $settings;
    }

    /**
     * Gets the relevant part (array of settings) of the ini file for a given pattern.
     *
     * @param  string $pattern
     *
     * @return array
     */
    private function getIniPart(string $pattern) : array
    {
        $pattern = strtolower($pattern);
        $patternhash = Pattern::getHashForParts($pattern);
        $subkey = SubKey::getIniPartCacheSubKey($patternhash);

        try {
            if (! $this->cache->hasItem('browscap.iniparts.' . $subkey, true)) {
                $this->logger->debug('cache key "browscap.iniparts.' . $subkey . '" not found');

                return [];
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->error(new \InvalidArgumentException('an error occured while checking a inipart in the cache', 0, $e));

            return [];
        }

        $success = null;

        try {
            $file = $this->cache->getItem('browscap.iniparts.' . $subkey, true, $success);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(new \InvalidArgumentException('an error occured while reading a inipart from the cache', 0, $e));

            return [];
        }

        if (! $success) {
            $this->logger->debug('cache key "browscap.iniparts.' . $subkey . '" not found');

            return [];
        }

        if (! is_array($file) || ! count($file)) {
            $this->logger->debug('cache key "browscap.iniparts.' . $subkey . '" was empty');

            return [];
        }

        $propertyFormatter = new PropertyFormatter(new PropertyHolder());
        $return = [];

        foreach ($file as $buffer) {
            [$tmpBuffer, $patterns] = explode("\t", $buffer, 2);

            if ($tmpBuffer === $patternhash) {
                try {
                    $return = \ExceptionalJSON\decode($patterns, true);
                } catch (DecodeErrorException $e) {
                    $this->logger->error('data for cache key "browscap.iniparts.' . $subkey . '" are not valid json');

                    return [];
                }

                foreach (array_keys($return) as $property) {
                    $return[$property] = $propertyFormatter->formatPropertyValue(
                        $return[$property],
                        (string) $property
                    );
                }

                break;
            }
        }

        return $return;
    }
}
