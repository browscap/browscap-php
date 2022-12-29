<?php

declare(strict_types=1);

namespace BrowscapPHP\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Data\PropertyFormatter;
use BrowscapPHP\Data\PropertyHolder;
use BrowscapPHP\Helper\QuoterInterface;
use JsonException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use UnexpectedValueException;

use function array_keys;
use function assert;
use function count;
use function explode;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use function strtolower;

use const JSON_THROW_ON_ERROR;

/**
 * extracts the data and the data for theses pattern from the ini content, optimized for PHP 5.5+
 */
final class GetData implements GetDataInterface
{
    /**
     * The cache instance
     */
    private BrowscapCacheInterface $cache;

    /**
     * a logger instance
     */
    private LoggerInterface $logger;

    private QuoterInterface $quoter;

    /** @throws void */
    public function __construct(BrowscapCacheInterface $cache, LoggerInterface $logger, QuoterInterface $quoter)
    {
        $this->cache  = $cache;
        $this->logger = $logger;
        $this->quoter = $quoter;
    }

    /**
     * Gets the settings for a given pattern (method calls itself to
     * get the data from the parent patterns)
     *
     * @param  string[] $settings
     *
     * @return string[]
     *
     * @throws UnexpectedValueException
     */
    public function getSettings(string $pattern, array $settings = []): array
    {
        // The pattern has been pre-quoted on generation to speed up the pattern search,
        // but for this check we need the unquoted version
        $unquotedPattern = $this->quoter->pregUnQuote($pattern);

        // Try to get settings for the pattern
        $addedSettings = $this->getIniPart($unquotedPattern);

        // set some additional data
        if (count($settings) === 0) {
            // The optimization with replaced digits get can now result in setting searches, for which we
            // won't find a result - so only add the pattern information, is settings have been found.
            //
            // If not an empty array will be returned and the calling function can easily check if a pattern
            // has been found.
            if (0 < count($addedSettings)) {
                $settings['browser_name_regex']   = '/^' . $pattern . '$/';
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
     * @return string[]
     *
     * @throws void
     */
    private function getIniPart(string $pattern): array
    {
        $pattern     = strtolower($pattern);
        $patternhash = Pattern::getHashForParts($pattern);
        $subkey      = SubKey::getIniPartCacheSubKey($patternhash);

        try {
            if (! $this->cache->hasItem('browscap.iniparts.' . $subkey, true)) {
                $this->logger->debug(
                    sprintf(
                        'cache key "browscap.iniparts.%s" for pattern "%s" not found',
                        $subkey,
                        $pattern,
                    ),
                );

                return [];
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                new \InvalidArgumentException(
                    sprintf(
                        'an error occured while checking inipart "browscap.iniparts.%s" in the cache',
                        $subkey,
                    ),
                    0,
                    $e,
                ),
            );

            return [];
        }

        $success = null;

        try {
            $file = $this->cache->getItem('browscap.iniparts.' . $subkey, true, $success);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                new \InvalidArgumentException(
                    sprintf(
                        'an error occured while reading inipart "browscap.iniparts.%s" from the cache',
                        $subkey,
                    ),
                    0,
                    $e,
                ),
            );

            return [];
        }

        if (! $success) {
            $this->logger->debug(
                sprintf(
                    'cache key "browscap.iniparts.%s" for pattern "%s" not found',
                    $subkey,
                    $pattern,
                ),
            );

            return [];
        }

        if (! is_array($file) || ! count($file)) {
            $this->logger->debug(
                sprintf(
                    'cache key "browscap.iniparts.%s" for pattern "%s" was empty',
                    $subkey,
                    $pattern,
                ),
            );

            return [];
        }

        $propertyFormatter = new PropertyFormatter(new PropertyHolder());
        $return            = [];

        foreach ($file as $buffer) {
            [$tmpBuffer, $patterns] = explode("\t", $buffer, 2);

            if ($tmpBuffer === $patternhash) {
                try {
                    $return = json_decode($patterns, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    $this->logger->error(
                        sprintf(
                            'data for cache key "browscap.iniparts.%s" for pattern "%s" are not valid json',
                            $subkey,
                            $pattern,
                        ),
                    );

                    return [];
                }

                assert(is_array($return));

                foreach (array_keys($return) as $property) {
                    $return[$property] = $propertyFormatter->formatPropertyValue(
                        $return[$property],
                        (string) $property,
                    );
                }

                break;
            }
        }

        return $return;
    }
}
