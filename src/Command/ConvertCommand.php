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

use BrowscapPHP\BrowscapUpdater;
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
 * command to convert a downloaded Browscap ini file and write it to the cache
 *
 * @category   Browscap-PHP
 * @author     Dave Olsen, http://dmolsen.com
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2015 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class ConvertCommand extends Command
{
    /**
     * @var BrowscapCacheInterface
     */
    private $cache = null;

    /**
     * @var string
     */
    private $defaultIniFile;

    /**
     * @var string
     */
    private $defaultCacheFolder;

    /**
     * @param string $defaultCacheFolder
     * @param string $defaultIniFile
     */
    public function __construct($defaultCacheFolder, $defaultIniFile)
    {
        $this->defaultCacheFolder = $defaultCacheFolder;
        $this->defaultIniFile     = $defaultIniFile;

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
            ->setName('browscap:convert')
            ->setDescription('Converts an existing browscap.ini file to a cache.php file.')
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Path to the browscap.ini file',
                $this->defaultIniFile
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

        $logger->info('initializing converting process');

        $browscap = new BrowscapUpdater();

        $browscap->setLogger($logger);
        $browscap->setCache($this->getCache($input));

        $logger->info('started converting local file');

        $file = ($input->getArgument('file') ? $input->getArgument('file') : ($this->defaultIniFile));

        $browscap->convertFile($file);

        $logger->info('finished converting local file');
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
