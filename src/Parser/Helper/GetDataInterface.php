<?php
declare(strict_types = 1);

namespace BrowscapPHP\Parser\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Helper\QuoterInterface;
use Psr\Log\LoggerInterface;

/**
 * interface for the parser dataHelper
 */
interface GetDataInterface
{
    /**
     * class contsructor
     *
     * @param \BrowscapPHP\Cache\BrowscapCacheInterface $cache
     * @param \Psr\Log\LoggerInterface                  $logger
     * @param \BrowscapPHP\Helper\QuoterInterface       $quoter
     */
    public function __construct(BrowscapCacheInterface $cache, LoggerInterface $logger, QuoterInterface $quoter);

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
    public function getSettings(string $pattern, array $settings = []) : array;
}
