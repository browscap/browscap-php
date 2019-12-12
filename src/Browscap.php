<?php
declare(strict_types = 1);

namespace BrowscapPHP;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Helper\Quoter;
use BrowscapPHP\Parser\ParserInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Browscap.ini parsing class with caching and update capabilities
 */
final class Browscap implements BrowscapInterface
{
    /**
     * Parser to use
     *
     * @var \BrowscapPHP\Parser\ParserInterface|null
     */
    private $parser;

    /**
     * Formatter to use
     *
     * @var \BrowscapPHP\Formatter\FormatterInterface
     */
    private $formatter;

    /**
     * The cache instance
     *
     * @var \BrowscapPHP\Cache\BrowscapCacheInterface
     */
    private $cache;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Browscap constructor.
     *
     * @param \Psr\SimpleCache\CacheInterface  $cache
     * @param LoggerInterface $logger
     */
    public function __construct(CacheInterface $cache, LoggerInterface $logger)
    {
        $this->cache = new BrowscapCache($cache, $logger);
        $this->logger = $logger;

        $this->formatter = new Formatter\PhpGetBrowser();
    }

    /**
     * Set theformatter instance to use for the getBrowser() result
     *
     * @param \BrowscapPHP\Formatter\FormatterInterface $formatter
     */
    public function setFormatter(Formatter\FormatterInterface $formatter) : void
    {
        $this->formatter = $formatter;
    }

    /**
     * Sets the parser instance to use
     *
     * @param \BrowscapPHP\Parser\ParserInterface $parser
     */
    public function setParser(ParserInterface $parser) : void
    {
        $this->parser = $parser;
    }

    /**
     * returns an instance of the used parser class
     *
     * @return \BrowscapPHP\Parser\ParserInterface
     */
    public function getParser() : ParserInterface
    {
        if (null === $this->parser) {
            $patternHelper = new Parser\Helper\GetPattern($this->cache, $this->logger);
            $dataHelper = new Parser\Helper\GetData($this->cache, $this->logger, new Quoter());

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
     * @throws \BrowscapPHP\Exception
     *
     * @return \stdClass              the object containing the browsers details.
     */
    public function getBrowser(?string $userAgent = null) : \stdClass
    {
        if (null === $this->cache->getVersion()) {
            // there is no active/warm cache available
            throw new Exception('there is no active cache available, please use the BrowscapUpdater and run the update command');
        }

        // Automatically detect the useragent
        if (! is_string($userAgent)) {
            $support = new Helper\Support($_SERVER);
            $userAgent = $support->getUserAgent();
        }

        try {
            // try to get browser data
            $formatter = $this->getParser()->getBrowser($userAgent);
        } catch (\UnexpectedValueException $e) {
            $this->logger->error(sprintf('could not parse useragent "%s"', $userAgent));
            $formatter = null;
        }

        // if return is still NULL, updates are disabled... in this
        // case we return an empty formatter instance
        if (null === $formatter) {
            $formatter = $this->formatter;
        }

        return $formatter->getData();
    }
}
