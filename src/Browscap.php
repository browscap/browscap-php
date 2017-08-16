<?php
declare(strict_types = 1);

namespace BrowscapPHP;

use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Formatter\FormatterInterface;
use BrowscapPHP\Helper\Quoter;
use BrowscapPHP\Parser\ParserInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

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
     * @var \BrowscapPHP\Cache\BrowscapCacheInterface
     */
    private $cache;

    /**
     * @var \Psr\Log\LoggerInterface|null
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
        $this->cache  = new BrowscapCache($cache);
        $this->logger = $logger;
    }

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
            $patternHelper = new Parser\Helper\GetPattern($this->cache, $this->logger);
            $dataHelper    = new Parser\Helper\GetData($this->cache, $this->logger, new Quoter());

            $this->parser = new Parser\Ini($patternHelper, $dataHelper, $this->getFormatter());
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
     * @return \stdClass              the object containing the browsers details. Array if
     *                                $return_array is set to true.
     */
    public function getBrowser(string $userAgent = null) : ?\stdClass
    {
        if (null === $this->cache->getVersion()) {
            // there is no active/warm cache available
            throw new Exception('there is no active cache available, please run the update command');
        }

        // Automatically detect the useragent
        if (! isset($userAgent)) {
            $support = new Helper\Support($_SERVER);
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
