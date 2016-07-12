<?php
/**
 * Copyright (c) 1998-2015 Browser Capabilities Project
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
 * @category   Browscap-PHP
 * @copyright  1998-2015 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace BrowscapPHP\Command;

use BrowscapPHP\Browscap;
use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Helper\LoggerHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WurflCache\Adapter\File;

/**
 * commands to parse a given useragent
 *
 * @category   Browscap-PHP
 * @author     Dave Olsen, http://dmolsen.com
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class ParserCommand extends Command
{
    /**
     * @var BrowscapCacheInterface
     */
    private $cache = null;

    /**
     * @var string
     */
    private $defaultCacheFolder;

    /**
     * @param string $defaultCacheFolder
     */
    public function __construct($defaultCacheFolder)
    {
        $this->defaultCacheFolder = $defaultCacheFolder;

        parent::__construct();
    }

    /**
     * @param \BrowscapPHP\Cache\BrowscapCacheInterface $cache
     *
     * @return $this
     */
    public function setCache(BrowscapCacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('browscap:parse')
            ->setDescription('Parses a user agent string and dumps the results.')
            ->addArgument(
                'user-agent',
                InputArgument::REQUIRED,
                'User agent string to analyze',
                null
            )
            ->addOption(
                'debug',
                'd',
                InputOption::VALUE_NONE,
                'Should the debug mode entered?'
            )
            ->addOption(
                'cache',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Where the cache files are located',
                $this->defaultCacheFolder
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loggerHelper = new LoggerHelper();
        $logger       = $loggerHelper->create($input->getOption('debug'));

        $browscap = new Browscap();

        $browscap
            ->setLogger($logger)
            ->setCache($this->getCache($input));

        $result = $browscap->getBrowser($input->getArgument('user-agent'));

        if (!defined('JSON_PRETTY_PRINT')) {
            // not defined in PHP 5.3
            define('JSON_PRETTY_PRINT', 128);
        }

        $output->writeln(json_encode($result, JSON_PRETTY_PRINT));
    }

    /**
     * @param InputInterface $input
     *
     * @return BrowscapCacheInterface
     */
    private function getCache(InputInterface $input)
    {
        if (null === $this->cache) {
            $cacheAdapter = new File([File::DIR => $input->getOption('cache')]);
            $this->cache  = new BrowscapCache($cacheAdapter);
        }

        return $this->cache;
    }
}
