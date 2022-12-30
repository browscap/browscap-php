<?php

declare(strict_types=1);

namespace BrowscapPHP\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use Generator;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;

use function count;
use function explode;
use function is_array;
use function sprintf;
use function str_repeat;
use function strlen;
use function trim;

/**
 * extracts the pattern and the data for theses pattern from the ini content, optimized for PHP 5.5+
 */
class GetPattern implements GetPatternInterface
{
    /**
     * The cache instance
     */
    private BrowscapCacheInterface $cache;

    /**
     * a logger instance
     */
    private LoggerInterface $logger;

    /** @throws void */
    public function __construct(BrowscapCacheInterface $cache, LoggerInterface $logger)
    {
        $this->cache  = $cache;
        $this->logger = $logger;
    }

    /**
     * Gets some possible patterns that have to be matched against the user agent. With the given
     * user agent string, we can optimize the search for potential patterns:
     * - We check the first characters of the user agent (or better: a hash, generated from it)
     * - We compare the length of the pattern with the length of the user agent
     *   (the pattern cannot be longer than the user agent!)
     *
     * @return Generator|string[]
     *
     * @throws void
     */
    public function getPatterns(string $userAgent): Generator
    {
        $starts = Pattern::getHashForPattern($userAgent, true);
        $length = strlen($userAgent);

        // add special key to fall back to the default browser
        $starts[] = str_repeat('z', 32);

        // get patterns, first for the given browser and if that is not found,
        // for the default browser (with a special key)
        foreach ($starts as $tmpStart) {
            $tmpSubkey = SubKey::getPatternCacheSubkey($tmpStart);

            try {
                if (! $this->cache->hasItem('browscap.patterns.' . $tmpSubkey, true)) {
                    $this->logger->debug(
                        sprintf(
                            'cache key "browscap.patterns.%s" for useragent "%s" not found',
                            $tmpSubkey,
                            $userAgent,
                        ),
                    );

                    continue;
                }
            } catch (InvalidArgumentException $e) {
                $this->logger->error(
                    new \InvalidArgumentException(
                        sprintf(
                            'an error occured while checking pattern "browscap.patterns.%s" in the cache',
                            $tmpSubkey,
                        ),
                        0,
                        $e,
                    ),
                );

                continue;
            }

            $success = null;

            try {
                $file = $this->cache->getItem('browscap.patterns.' . $tmpSubkey, true, $success);
            } catch (InvalidArgumentException $e) {
                $this->logger->error(
                    new \InvalidArgumentException(
                        sprintf(
                            'an error occured while reading pattern "browscap.patterns.%s" from the cache',
                            $tmpSubkey,
                        ),
                        0,
                        $e,
                    ),
                );

                continue;
            }

            if (! $success) {
                $this->logger->debug(
                    sprintf(
                        'cache key "browscap.patterns.%s" for useragent "%s" not found',
                        $tmpSubkey,
                        $userAgent,
                    ),
                );

                continue;
            }

            if (! is_array($file) || ! count($file)) {
                $this->logger->debug(
                    sprintf(
                        'cache key "browscap.patterns.%s" for useragent "%s" was empty',
                        $tmpSubkey,
                        $userAgent,
                    ),
                );

                continue;
            }

            $found = false;

            foreach ($file as $buffer) {
                [$tmpBuffer, $len, $patterns] = explode("\t", $buffer, 3);

                if ($tmpBuffer === $tmpStart) {
                    if ($len <= $length) {
                        yield trim($patterns);
                    }

                    $found = true;
                } elseif ($found === true) {
                    break;
                }
            }
        }

        yield '';
    }
}
