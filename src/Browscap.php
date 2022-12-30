<?php

declare(strict_types=1);

namespace BrowscapPHP;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Formatter\FormatterInterface;
use BrowscapPHP\Helper\Quoter;
use BrowscapPHP\Parser\ParserInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use stdClass;
use UnexpectedValueException;

use function is_string;
use function sprintf;

/**
 * Browscap.ini parsing class with caching and update capabilities
 */
final class Browscap implements BrowscapInterface
{
    /**
     * Parser to use
     */
    private ?ParserInterface $parser = null;

    /**
     * Formatter to use
     */
    private FormatterInterface $formatter;

    /**
     * The cache instance
     */
    private BrowscapCacheInterface $cache;

    private LoggerInterface $logger;

    /** @throws void */
    public function __construct(CacheInterface $cache, LoggerInterface $logger)
    {
        $this->cache  = new BrowscapCache($cache, $logger);
        $this->logger = $logger;

        $this->formatter = new Formatter\PhpGetBrowser();
    }

    /**
     * Set theformatter instance to use for the getBrowser() result
     *
     * @throws void
     */
    public function setFormatter(Formatter\FormatterInterface $formatter): void
    {
        $this->formatter = $formatter;
    }

    /**
     * Sets the parser instance to use
     *
     * @throws void
     */
    public function setParser(ParserInterface $parser): void
    {
        $this->parser = $parser;
    }

    /**
     * returns an instance of the used parser class
     *
     * @throws void
     */
    public function getParser(): ParserInterface
    {
        if ($this->parser === null) {
            $patternHelper = new Parser\Helper\GetPattern($this->cache, $this->logger);
            $dataHelper    = new Parser\Helper\GetData($this->cache, $this->logger, new Quoter());

            $this->parser = new Parser\Ini($patternHelper, $dataHelper, $this->formatter);
        }

        return $this->parser;
    }

    /**
     * parses the given user agent to get the information about the browser
     *
     * if no user agent is given, it uses {@see \BrowscapPHP\Helper\Support} to get it
     *
     * @param string $userAgent the user agent string
     *
     * @return stdClass the object containing the browsers details.
     *
     * @throws Exception
     */
    public function getBrowser(?string $userAgent = null): stdClass
    {
        if ($this->cache->getVersion() === null) {
            // there is no active/warm cache available
            throw new Exception('there is no active cache available, please use the BrowscapUpdater and run the update command');
        }

        // Automatically detect the useragent
        if (! is_string($userAgent)) {
            $support   = new Helper\Support($_SERVER);
            $userAgent = $support->getUserAgent();
        }

        try {
            // try to get browser data
            $formatter = $this->getParser()->getBrowser($userAgent);
        } catch (UnexpectedValueException $e) {
            $this->logger->error(sprintf('could not parse useragent "%s"', $userAgent));
            $formatter = null;
        }

        // if return is still NULL, updates are disabled... in this
        // case we return an empty formatter instance
        if ($formatter === null) {
            $formatter = $this->formatter;
        }

        return $formatter->getData();
    }
}
