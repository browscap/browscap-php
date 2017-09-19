<?php
declare(strict_types = 1);

namespace BrowscapPHP\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * extracts the pattern and the data for theses pattern from the ini content, optimized for PHP 5.5+
 */
class GetPattern implements GetPatternInterface
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
     * class contructor
     *
     * @param \BrowscapPHP\Cache\BrowscapCacheInterface $cache
     * @param \Psr\Log\LoggerInterface                  $logger
     */
    public function __construct(BrowscapCacheInterface $cache, LoggerInterface $logger)
    {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Gets some possible patterns that have to be matched against the user agent. With the given
     * user agent string, we can optimize the search for potential patterns:
     * - We check the first characters of the user agent (or better: a hash, generated from it)
     * - We compare the length of the pattern with the length of the user agent
     *   (the pattern cannot be longer than the user agent!)
     *
     * @param string $userAgent
     *
     * @return \Generator
     */
    public function getPatterns(string $userAgent) : \Generator
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
                    $this->logger->debug('cache key "browscap.patterns.' . $tmpSubkey . '" not found');

                    continue;
                }
            } catch (InvalidArgumentException $e) {
                $this->logger->error(new \InvalidArgumentException('an error occured while checking a pattern in the cache', 0, $e));

                continue;
            }

            $success = null;

            try {
                $file = $this->cache->getItem('browscap.patterns.' . $tmpSubkey, true, $success);
            } catch (InvalidArgumentException $e) {
                $this->logger->error(new \InvalidArgumentException('an error occured while reading the pattern data data from the cache', 0, $e));

                continue;
            }

            if (! $success) {
                $this->logger->debug('cache key "browscap.patterns.' . $tmpSubkey . '" not found');

                continue;
            }

            if (! is_array($file) || ! count($file)) {
                $this->logger->debug('cache key "browscap.patterns.' . $tmpSubkey . '" was empty');

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
                } elseif (true === $found) {
                    break;
                }
            }
        }

        yield '';
    }
}
