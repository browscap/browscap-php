<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
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
 * @package    Command
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Command;

use phpbrowscap\Browscap;
use phpbrowscap\Util\Logfile\ReaderCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use phpbrowscap\Exception\InvalidArgumentException;
use phpbrowscap\Exception\ReaderException;
use phpbrowscap\Helper\LoggerHelper;
use phpbrowscap\Cache\BrowscapCache;
use phpbrowscap\Util\Logfile\ReaderFactory;
use phpbrowscap\Helper\IniLoader;

/**
 * commands to parse a log file and parse the useragents in it
 *
 * @category   Browscap-PHP
 * @package    Command
 * @author     Dave Olsen, http://dmolsen.com
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class LogfileCommand extends Command
{
    /**
     * @var \phpbrowscap\Cache\BrowscapCache
     */
    private $cache = null;

    /**
     * @var array
     */
    private $undefinedClients = array();

    /**
     * @param \phpbrowscap\Cache\BrowscapCache $cache
     */
    public function __construct(BrowscapCache $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('browscap:log')
            ->setDescription('Parses the supplied webserver log file.')
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Path to output log file',
                null
            )
            ->addOption(
                'log-file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Path to a webserver log file'
            )
            ->addOption(
                'log-dir',
                'd',
                InputOption::VALUE_REQUIRED,
                'Path to webserver log directory'
            )
            ->addOption(
                'include',
                'i',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Include glob expressions for log files in the log directory',
                array('*.log', '*.log*.gz', '*.log*.bz2')
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exclude glob expressions for log files in the log directory',
                array('*error*')
            )
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'Should the debug mode entered?'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \phpbrowscap\Exception\InvalidArgumentException
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('log-file') && !$input->getOption('log-dir')) {
            throw InvalidArgumentException::oneOfCommandArguments('log-file', 'log-dir');
        }

        $loggerHelper = new LoggerHelper();
        $logger       = $loggerHelper->create($input->getOption('debug'));

        $browscap = $this->getBrowscap();
        $loader   = new IniLoader();

        $browscap
            ->setLogger($logger)
            ->setCache($this->cache)
        ;

        /** @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($this->getFiles($input) as $file) {
            $path  = $this->getPath($file);

            $loader->setLocalFile($path);
            $internalLoader = $loader->getLoader();
            $collection     = ReaderFactory::factory();

            $logger->info('Analyzing file "' . $file->getPathname() . '"');

            if ($internalLoader->isSupportingLoadingLines()) {
                if (!$internalLoader->init($path)) {
                    $logger->info('Skipping empty file "' . $file->getPathname() . '"');
                    continue;
                }

                $count      = 1;
                $totalCount = 1;

                while ($internalLoader->isValid()) {
                    $count = $this->handleLine(
                        $collection,
                        $browscap,
                        $output,
                        $internalLoader->getLine(),
                        $count,
                        $totalCount
                    );
                    $totalCount++;
                }

                $internalLoader->close();
                $totalCount--;
            } else {
                $lines = file($path);

                if (empty($lines)) {
                    $logger->info('Skipping empty file "' . $file->getPathname() . '"');
                    continue;
                }

                $count      = 1;
                $totalCount = count($lines);

                foreach ($lines as $line) {
                    $count = $this->handleLine(
                        $collection,
                        $browscap,
                        $output,
                        $line,
                        $count,
                        $totalCount
                    );
                }
            }
            $this->outputProgress($output, '', $count - 1, $totalCount, true);
            $output->writeln('');
        }

        $fs = new Filesystem();
        $fs->dumpFile($input->getArgument('output'), join(PHP_EOL, array_unique($this->undefinedClients)));
    }

    /**
     * @param integer $count
     * @param integer $totalCount
     */
    private function handleLine(ReaderCollection $collection, Browscap $browscap, OutputInterface $output, $line, $count, $totalCount)
    {
        try {
            $userAgentString = $collection->read($line);
        } catch (ReaderException $e) {
            return $this->outputProgress($output, 'E', $count, $totalCount);
        }

        $result = $this->getResult($browscap->getBrowser($userAgentString));

        if ($result !== '.') {
            $this->undefinedClients[] = $userAgentString;
        }

        return $this->outputProgress($output, $result, $count, $totalCount);
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string                                            $result
     * @param integer                                           $count
     * @param integer                                           $totalCount
     * @param bool                                              $end
     *
     * @return int
     */
    private function outputProgress(OutputInterface $output, $result, $count, $totalCount, $end = false)
    {
        if (($count % 70) === 0 || $end) {
            $formatString = '%s  %' . strlen($totalCount) . 'd / %-' . strlen($totalCount) . 'd (%3d%%)';
            $result = $end ? str_repeat(' ', 70 - ($count % 70)) : $result;
            $output->writeln(sprintf($formatString, $result, $count, $totalCount, $count / $totalCount * 100));
        } else {
            $output->write($result);
        }

        return $count + 1;
    }

    /**
     * @param \stdClass $result
     *
     * @return string
     */
    private function getResult(\stdClass $result)
    {
        if ($result->browser_type === 'Bot/Crawler') {
            return '.';
        } elseif ($result->browser === 'Default Browser') {
            return 'B';
        } elseif ($result->platform === 'unknown') {
            return 'P';
        } elseif ($result->device_type === 'unknown') {
            return 'D';
        }

        return '.';
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return \Symfony\Component\Finder\Finder
     */
    private function getFiles(InputInterface $input)
    {
        $finder = Finder::create();

        if ($input->getOption('log-file')) {
            $file = $input->getOption('log-file');
            $finder->append(Finder::create()->in(dirname($file))->name(basename($file)));
        }

        if ($input->getOption('log-dir')) {
            $dirFinder = Finder::create()
                ->in($input->getOption('log-dir'));
            array_map(array($dirFinder, 'name'), $input->getOption('include'));
            array_map(array($dirFinder, 'notName'), $input->getOption('exclude'));

            $finder->append($dirFinder);
        }

        return $finder;
    }

    /**
     * @param \Symfony\Component\Finder\SplFileInfo $file
     *
     * @return string
     */
    private function getPath(SplFileInfo $file)
    {
        switch ($file->getExtension()) {
            case 'gz':
                $path = 'compress.zlib://' . $file->getPathname();
                break;
            case 'bz2':
                $path = 'compress.bzip2://' . $file->getPathname();
                break;
            default:
                $path = $file->getPathname();
                break;
        }

        return $path;
    }
    
    private function getBrowscap()
    {
        return new Browscap();
    }
}
