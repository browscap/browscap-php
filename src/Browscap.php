<?php
declare(strict_types=1);

namespace BrowscapPHP;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Formatter\FormatterInterface;
use BrowscapPHP\Helper\Quoter;
use BrowscapPHP\Parser\ParserInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WurflCache\Adapter\AdapterInterface;
use WurflCache\Adapter\File;

/**
 * Browscap.ini parsing class with caching and update capabilities
 */
final class Browscap
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
     * @var \BrowscapPHP\Formatter\FormatterInterface|null
     */
    private $formatter;

    /**
     * The cache instance
     *
     * @var \BrowscapPHP\Cache\BrowscapCacheInterface|null
     */
    private $cache;

    /**
     * @var \Psr\Log\LoggerInterface|null
     */
    private $logger;

    /**
     * Set theformatter instance to use for the getBrowser() result
     *
     * @param \BrowscapPHP\Formatter\FormatterInterface $formatter
     *
     * @return \BrowscapPHP\Browscap
     */
    public function setFormatter(Formatter\FormatterInterface $formatter) : self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @return \BrowscapPHP\Formatter\FormatterInterface
     */
    public function getFormatter() : FormatterInterface
    {
        if (null === $this->formatter) {
            $this->setFormatter(new Formatter\PhpGetBrowser());
        }

        return $this->formatter;
    }

    /**
     * Gets a cache instance
     *
     * @return \BrowscapPHP\Cache\BrowscapCacheInterface
     */
    public function getCache() : BrowscapCacheInterface
    {
        if (null === $this->cache) {
            $cacheDirectory = __DIR__ . '/../resources/';

            $cacheAdapter = new File(
                [File::DIR => $cacheDirectory]
            );

            $this->cache = new BrowscapCache($cacheAdapter);
        }

        return $this->cache;
    }

    /**
     * Sets a cache instance
     *
     * @param \BrowscapPHP\Cache\BrowscapCacheInterface|\WurflCache\Adapter\AdapterInterface $cache
     *
     * @throws \BrowscapPHP\Exception
     * @return \BrowscapPHP\Browscap
     */
    public function setCache($cache) : self
    {
        if ($cache instanceof BrowscapCacheInterface) {
            $this->cache = $cache;
        } elseif ($cache instanceof AdapterInterface) {
            $this->cache = new BrowscapCache($cache);
        } else {
            throw new Exception(
                'the cache has to be an instance of \BrowscapPHP\Cache\BrowscapCacheInterface or '
                . 'an instanceof of \WurflCache\Adapter\AdapterInterface',
                Exception::CACHE_INCOMPATIBLE
            );
        }

        return $this;
    }

    /**
     * Sets the parser instance to use
     *
     * @param \BrowscapPHP\Parser\ParserInterface $parser
     *
     * @return \BrowscapPHP\Browscap
     */
    public function setParser(ParserInterface $parser) : self
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * returns an instance of the used parser class
     *
     * @return \BrowscapPHP\Parser\ParserInterface
     */
    public function getParser() : ParserInterface
    {
        if (null === $this->parser) {
            $cache  = $this->getCache();
            $logger = $this->getLogger();
            $quoter = new Quoter();

            $patternHelper = new Parser\Helper\GetPattern($cache, $logger);
            $dataHelper    = new Parser\Helper\GetData($cache, $logger, $quoter);

            $this->parser = new Parser\Ini($patternHelper, $dataHelper, $this->getFormatter());
        }

        return $this->parser;
    }

    /**
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \BrowscapPHP\Browscap
     */
    public function setLogger(LoggerInterface $logger) : self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * returns a logger instance
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger() : LoggerInterface
    {
        if (null === $this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * parses the given user agent to get the information about the browser
     *
     * if no user agent is given, it uses {@see \BrowscapPHP\Helper\Support} to get it
     *
     * @param string $userAgent the user agent string
     *
     * @throws \BrowscapPHP\Exception
     * @return \stdClass              the object containing the browsers details. Array if
     *                                $return_array is set to true.
     */
    public function getBrowser(string $userAgent = null) : \stdClass
    {
        if (null === $this->getCache()->getVersion()) {
            // there is no active/warm cache available
            throw new Exception('there is no active cache available, please run the update command');
        }

        // Automatically detect the useragent
        if (!isset($userAgent)) {
            $support   = new Helper\Support($_SERVER);
            $userAgent = $support->getUserAgent();
        }

        // try to get browser data
        $formatter = $this->getParser()->getBrowser($userAgent);

        // if return is still NULL, updates are disabled... in this
        // case we return an empty formatter instance
        if ($formatter === null) {
            return $this->getFormatter()->getData();
        }

        return $formatter->getData();
    }
}
