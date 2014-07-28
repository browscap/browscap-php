<?php

namespace phpbrowscap;


use phpbrowscap\Helper\Converter;
use phpbrowscap\Cache\BrowscapCache;
use WurflCache\Adapter\NullStorage;

/**
 * Browscap.ini parsing class with caching and update capabilities
 *
 * PHP version 5
 *
 * Copyright (c) 2006-2012 Jonathan Stoppani
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    Browscap
 * @author     Jonathan Stoppani <jonathan@stoppani.name>
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @author     Mikołaj Misiurewicz <quentin389+phpb@gmail.com>
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/GaretJax/phpbrowscap/
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

    /** @var \Monolog\Logger */
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

            $defaultproperties = array(
                'browser_name_regex' => null,
                'browser_name_pattern' => null,
                'Parent' => null,
                'Comment' => 'DefaultProperties',
                'Browser' => 'DefaultProperties',
                'Browser_Type' => 'unknown',
                'Browser_Bits' => '0',
                'Browser_Maker' => 'unknown',
                'Browser_Modus' => 'unknown',
                'Version' => '0.0',
                'MajorVer' => '0',
                'MinorVer' => '0',
                'Platform' => 'unknown',
                'Platform_Version' => 'unknown',
                'Platform_Description' => 'unknown',
                'Platform_Bits' => '0',
                'Platform_Maker' => 'unknown',
                'Alpha' => 'false',
                'Beta' => 'false',
                'Win16' => 'false',
                'Win32' => 'false',
                'Win64' => 'false',
                'Frames' => 'false',
                'IFrames' => 'false',
                'Tables' => 'false',
                'Cookies' => 'false',
                'BackgroundSounds' => 'false',
                'JavaScript' => 'false',
                'VBScript' => 'false',
                'JavaApplets' => 'false',
                'ActiveXControls' => 'false',
                'isMobileDevice' => 'false',
                'isTablet' => 'false',
                'isSyndicationReader' => 'false',
                'Crawler' => 'false',
                'CssVersion' => '0',
                'AolVersion' => '0',
                'Device_Name' => 'unknown',
                'Device_Maker' => 'unknown',
                'Device_Type' => 'unknown',
                'Device_Pointing_Method' => 'unknown',
                'Device_Code_Name' => 'unknown',
                'Device_Brand_Name' => 'unknown',
                'RenderingEngine_Name' => 'unknown',
                'RenderingEngine_Version' => 'unknown',
                'RenderingEngine_Description' => 'unknown',
                'RenderingEngine_Maker' => 'unknown',
            );

            $this->formatter->setData($defaultproperties);
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
     * @param \Monolog\Logger $logger
     *
     * @return \phpbrowscap\Browscap
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * XXX parse
     *
     * Gets the information about the browser by User Agent
     *
     * @param string $userAgent the user agent string
     *
     * @throws Exception
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
     * @param string $iniFile
     * @throws \phpbrowscap\Exception\FileNotFoundException
     */
    public function convertFile($iniFile)
    {
        $converter = new Converter();

        $converter
            ->setLogger($logger)
            ->setCache($cache)
            ->convertFile($iniFile)
        ;
    }

    /**
     * @param string $iniString
     */
    public function convertString($iniString)
    {
        $converter = new Converter();

        $converter
            ->setLogger($logger)
            ->setCache($cache)
            ->convertString($iniString)
        ;
    }
}
