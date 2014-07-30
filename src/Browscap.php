<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Refer to the LICENSE file distributed with this package.
 *
 * @category   Browscap-PHP
 * @package    Browscap
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */

namespace phpbrowscap;

use phpbrowscap\Helper\Converter;
use phpbrowscap\Cache\BrowscapCache;
use WurflCache\Adapter\NullStorage;
use phpbrowscap\Helper\IniLoader;
use Psr\Log\LoggerInterface;

/**
 * Browscap.ini parsing class with caching and update capabilities
 *
 * @category   Browscap-PHP
 * @package    Browscap
 * @author     Jonathan Stoppani <jonathan@stoppani.name>
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @author     Mikołaj Misiurewicz <quentin389+phpb@gmail.com>
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class Browscap
{
    /**
     * Current version of the class.
     */
    const VERSION = '3.0a';

    /**
     * Parser to use
     *
     * @var \phpbrowscap\Parser\Ini
     */
    private $parser = null;

    /**
     * Formatter to use
     *
     * @var \phpbrowscap\Formatter\FormatterInterface
     */
    private $formatter = null;

    /**
     * The cache instance
     *
     * @var \phpbrowscap\Cache\BrowscapCache
     */
    private $cache = null;

    /** @var \Psr\Log\LoggerInterface */
    private $logger = null;

    /**
     * Set theformatter instance to use for the getBrowser() result
     *
     * @param \phpbrowscap\Formatter\FormatterInterface $formatter
     *
     * @return \phpbrowscap\Browscap
     */
    public function setFormatter(Formatter\FormatterInterface $formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @return \phpbrowscap\Formatter\FormatterInterface
     */
    public function getFormatter()
    {
        if (null === $this->formatter) {
            $this->setFormatter(new Formatter\PhpGetBrowser());
        }

        return $this->formatter;
    }

    /**
     * Gets a cache instance
     *
     * @return \phpbrowscap\Cache\BrowscapCache
     */
    public function getCache()
    {
        if (null === $this->cache) {
            $resourceDirectory = __DIR__  . '/../resources/';

            $cacheAdapter = new \WurflCache\Adapter\File(
                array(\WurflCache\Adapter\File::DIR => $resourceDirectory)
            );

            $this->cache = new BrowscapCache($cacheAdapter);
        }

        return $this->cache;
    }

    /**
     * Sets a cache instance
     *
     * @param \phpbrowscap\Cache\BrowscapCache $cache
     *
     * @return \phpbrowscap\Browscap
     */
    public function setCache(BrowscapCache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Sets the parser instance to use
     *
     * @param \phpbrowscap\Parser\ParserInterface $parser
     *
     * @return \phpbrowscap\Browscap
     */
    public function setParser(Parser\ParserInterface $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * returns an instance of the used parser class
     *
     * @return Parser\ParserInterface
     */
    public function getParser()
    {
        if (null === $this->parser) {
            $this->setParser(new Parser\Ini());
        }

        // generators are supported from PHP 5.5, so select the correct parser version to use
        // (the version without generators requires about 2-3x the memory and is a bit slower)
        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            $helper = new Parser\Helper\GetPattern();
        } else {
            $helper = new Parser\Helper\GetPatternLt55();
        }

        $helper->setCache($this->getCache());

        $this->parser
            ->setHelper($helper)
            ->setFormatter($this->getFormatter())
            ->setCache($this->getCache())
            ->setLogger($this->logger);
        ;


        return $this->parser;
    }

    /**
     * Sets a logger instance
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \phpbrowscap\Browscap
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * parses the given user agent to get the information about the browser
     * 
     * if no user agent is given, it uses {@see \phpbrowscap\Helper\Support} to get it
     *
     * @param string $userAgent the user agent string
     *
     * @throws \phpbrowscap\Exception
     * @return \stdClass|array  the object containing the browsers details. Array if
     *                    $return_array is set to true.
     */
    public function getBrowser($userAgent = null)
    {
        // Automatically detect the useragent
        if (!isset($userAgent)) {
            $support   = new Helper\Support($_SERVER);
            $userAgent = $support->getUserAgent();
        }

        // try to get browser data
        $return = $this->getParser()->getBrowser($userAgent);

        // if return is still NULL, updates are disabled... in this
        // case we return an empty formatter instance
        if ($return === null) {
            return $this->getFormatter()->getData();
        }

        return $return->getData();
    }

    /**
     * reads and parses an ini file and writes the results into the cache
     *
     * @param string $iniFile
     * @throws \phpbrowscap\Exception\FileNotFoundException
     */
    public function convertFile($iniFile)
    {
        $loader = new IniLoader();
        $loader->setLocalFile($iniFile);
        
        $converter = new Converter();

        $converter
            ->setLogger($this->logger)
            ->setCache($this->getCache())
            ->convertString($loader->load())
        ;
    }

    /**
     * reads and parses an ini string and writes the results into the cache
     *
     * @param string $iniString
     */
    public function convertString($iniString)
    {
        $converter = new Converter();

        $converter
            ->setLogger($this->logger)
            ->setCache($this->getCache())
            ->convertString($iniString)
        ;
    }
    
    /**
     * fetches a remote file and stores it into a local folder
     *
     * @param string $file       The name of the file where to store the remote content
     * @param string $remoteFile The code for the remote file to load
     */
    public function fetch($file, $remoteFile = IniLoader::PHP_INI)
    {
        $loader = new IniLoader();
        $loader->setRemoteFilename($remoteFile);

        $logger->debug('started fetching remote file');
        
        $content = $loader
            ->setLogger($this->logger)
            ->load()
        ;

        if ($content === false) {
            $error = error_get_last();
            throw FetcherException::httpError($loader->getRemoteIniUrl(), $error['message']);
        }
        
        $logger->debug('finished fetching remote file');
        $logger->debug('started storing remote file into local file');
        
        $fs = new Filesystem();
        $fs->dumpFile($file, $content);
        
        $logger->debug('finished storing remote file into local file');
    }
    
    /**
     * fetches a remote file, parses it and writes the result into the cache
     *
     * if the local stored information are in the same version as the remote data no actions are
     * taken
     *
     * @param string $remoteFile The code for the remote file to load
     */
    public function update($remoteFile = IniLoader::PHP_INI)
    {
        $loader = new IniLoader();
        $loader->setRemoteFilename($remoteFile);

        $logger->debug('started fetching remote file');
        
        $content = $loader
            ->setLogger($this->logger)
            ->load()
        ;

        if ($content === false) {
            $error = error_get_last();
            throw FetcherException::httpError($loader->getRemoteIniUrl(), $error['message']);
        }
        
        $logger->debug('finished fetching remote file');
        
        $converter = new Converter();

        $converter
            ->setLogger($this->logger)
            ->setCache($this->getCache())
            ->convertString($content)
        ;
    }
}
