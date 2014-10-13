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
use phpbrowscap\Exception\UnknownBrowserException;
use phpbrowscap\Exception\UnknownBrowserTypeException;
use phpbrowscap\Exception\UnknownDeviceException;
use phpbrowscap\Exception\UnknownEngineException;
use phpbrowscap\Exception\UnknownPlatformException;
use phpbrowscap\Helper\Filesystem;
use phpbrowscap\Util\Logfile\ReaderCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
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

    private $uas         = array();
    private $uasWithType = array();

    private $countOk = 0;
    private $countNok = 0;
    private $totalCount = 0;

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
     * @throws \UnexpectedValueException
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

        $browscap   = $this->getBrowscap();
        $loader     = new IniLoader();
        $collection = ReaderFactory::factory();
        $fs         = new Filesystem();

        $browscap
            ->setLogger($logger)
            ->setCache($this->cache)
        ;

        /** @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($this->getFiles($input) as $file) {
            $this->uas = array();
            $path      = $this->getPath($file);

            $loader->setLocalFile($path);
            $internalLoader = $loader->getLoader();

            $this->countOk  = 0;
            $this->countNok = 0;

            $logger->info('Analyzing file "' . $file->getPathname() . '"');

            if ($internalLoader->isSupportingLoadingLines()) {
                if (!$internalLoader->init($path)) {
                    $logger->info('Skipping empty file "' . $file->getPathname() . '"');
                    continue;
                }

                $this->totalCount = 1;

                while ($internalLoader->isValid()) {
                    $this->handleLine(
                        $output,
                        $collection,
                        $browscap,
                        $internalLoader->getLine()
                    );

                    $this->totalCount++;
                }

                $internalLoader->close();
                $this->totalCount--;
            } else {
                $lines = file($path);

                if (empty($lines)) {
                    $logger->info('Skipping empty file "' . $file->getPathname() . '"');
                    continue;
                }

                $this->totalCount = count($lines);

                foreach ($lines as $line) {
                    $this->handleLine(
                        $output,
                        $collection,
                        $browscap,
                        $line
                    );
                }
            }

            $this->outputProgress($output, '', true);

            arsort($this->uas, SORT_NUMERIC);

            try {
                $fs->dumpFile($input->getArgument('output') . '/output.sql', $this->createSqlContent());
            } catch (IOException $e) {
                // do nothing
            }

            try {
                $fs->dumpFile(
                    $input->getArgument('output') . '/output.txt',
                    implode(PHP_EOL, array_unique($this->undefinedClients))
                );
            } catch (IOException $e) {
                // do nothing
            }

            try {
                $fs->dumpFile(
                    $input->getArgument('output') . '/output-with-amount.txt',
                    $this->createAmountContent()
                );
            } catch (IOException $e) {
                // do nothing
            }

            try {
                $fs->dumpFile(
                    $input->getArgument('output') . '/output-with-amount-and-type.txt',
                    $this->createAmountTypeContent()
                );
            } catch (IOException $e) {
                // do nothing
            }
        }

        try {
            $fs->dumpFile($input->getArgument('output') . '/output.sql', $this->createSqlContent());
        } catch (IOException $e) {
            // do nothing
        }

        try {
            $fs->dumpFile(
                $input->getArgument('output') . '/output.txt',
                implode(PHP_EOL, array_unique($this->undefinedClients))
            );
        } catch (IOException $e) {
            throw new \UnexpectedValueException('writing to file "' . $outputFile . '" failed', 0, $e);
        }

        try {
            $fs->dumpFile(
                $input->getArgument('output') . '/output-with-amount.txt',
                $this->createAmountContent()
            );
        } catch (IOException $e) {
            // do nothing
        }

        try {
            $fs->dumpFile(
                $input->getArgument('output') . '/output-with-amount-and-type.txt',
                $this->createAmountTypeContent()
            );
        } catch (IOException $e) {
            // do nothing
        }
    }

    private function createSqlContent()
    {
        $content = '';
        
        arsort($this->uas, SORT_NUMERIC);

        foreach ($this->uas as $agentOfLine => $count) {
            $content .= "INSERT INTO `agents` (`agent`, `count`) VALUES ('" . addslashes($agentOfLine) . "', " . addslashes($count) . ") ON DUPLICATE KEY UPDATE `count`=`count`+" . addslashes($count) . ";\n";
        }

        return $content;
    }

    private function createAmountContent()
    {
        $counts = array();

        foreach ($this->uasWithType as $uas) {
            foreach ($uas as $userAgentString => $count) {
                if (isset($counts[$userAgentString])) {
                    $counts[$userAgentString] += $count;
                } else {
                    $counts[$userAgentString] = $count;
                }
            }
        }

        $content = '';
        
        arsort($counts, SORT_NUMERIC);

        foreach ($counts as $agentOfLine => $count) {
            $content .= "$count\t$agentOfLine\n";
        }

        return $content;
    }

    private function createAmountTypeContent()
    {
        $content = '';
        $types   = array('B', 'T', 'P', 'D', 'N', 'U');

        foreach ($types as $type) {
            if (!isset($this->uasWithType[$type])) {
                continue;
            }
            
            arsort($this->uasWithType[$type], SORT_NUMERIC);

            foreach ($this->uasWithType[$type] as $agentOfLine => $count) {
                $content .= "$type\t$count\t$agentOfLine\n";
            }
        }

        return $content;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \phpbrowscap\Util\Logfile\ReaderCollection        $collection
     * @param \phpbrowscap\Browscap                             $browscap
     * @param integer                                           $line
     *
     * @throws UnknownBrowserException
     * @throws UnknownBrowserTypeException
     * @throws UnknownDeviceException
     * @throws UnknownEngineException
     * @throws UnknownPlatformException
     * @throws \Exception
     */
    private function handleLine(OutputInterface $output, ReaderCollection $collection, Browscap $browscap, $line)
    {
        try {
            $userAgentString = $collection->read($line);

            try {
                $this->getResult($browscap->getBrowser($userAgentString));
            } catch (\Exception $e) {
                $this->undefinedClients[] = $userAgentString;

                throw $e;
            }

            $type = '.';
            $this->countOk++;
        } catch (ReaderException $e) {
            $type = 'E';
            $this->countNok++;
        } catch (UnknownBrowserTypeException $e) {
            $type = 'T';
            $this->countNok++;
        } catch (UnknownBrowserException $e) {
            $type = 'B';
            $this->countNok++;
        } catch (UnknownPlatformException $e) {
            $type = 'P';
            $this->countNok++;
        } catch (UnknownDeviceException $e) {
            $type = 'D';
            $this->countNok++;
        } catch (UnknownEngineException $e) {
            $type = 'N';
            $this->countNok++;
        } catch (\Exception $e) {
            $type = 'U';
            $this->countNok++;
        }

        $this->outputProgress($output, $type);

        // count all useragents
        if (isset($this->uas[$userAgentString])) {
            $this->uas[$userAgentString]++;
        } else {
            $this->uas[$userAgentString] = 1;
        }

        if ('.' !== $type && 'E' !== $type) {
            // count all undetected useragents grouped by detection error
            if (!isset($this->uasWithType[$type])) {
                $this->uasWithType[$type] = array();
            }

            if (isset($this->uasWithType[$type][$userAgentString])) {
                $this->uasWithType[$type][$userAgentString]++;
            } else {
                $this->uasWithType[$type][$userAgentString] = 1;
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string                                            $result
     * @param bool                                              $end
     *
     * @return int
     */
    private function outputProgress(OutputInterface $output, $result, $end = false)
    {
        if (($this->totalCount % 70) === 0 || $end) {
            $formatString = '  %' . strlen($this->countOk) . 'd OK, %' . strlen($this->countNok) . 'd NOK, Summary %'
                . strlen($this->totalCount) . 'd';

            if ($end) {
                $result = str_pad($result, 70 - ($this->totalCount % 70), ' ', STR_PAD_RIGHT);
            }

            $endString = sprintf($formatString, $this->countOk, $this->countNok, $this->totalCount);

            $output->writeln($result . $endString);

            return;
        }

        $output->write($result);
    }

    /**
     * @param \stdClass $result
     *
     * @return string
     */
    private function getResult(\stdClass $result)
    {
        if ('Default Browser' === $result->browser) {
            throw new UnknownBrowserException('unknwon browser found');
        }

        if ('unknown' === $result->browser_type) {
            throw new UnknownBrowserTypeException('unknwon browser type found');
        }

        if (in_array($result->browser_type, array('Bot/Crawler', 'Library'))) {
            return '.';
        }

        if ('unknown' === $result->platform) {
            throw new UnknownPlatformException('unknown platform found');
        }

        if ('unknown' === $result->device_type) {
            throw new UnknownDeviceException('unknwon device type found');
        }

        if ('unknown' === $result->renderingengine_name) {
            throw new UnknownEngineException('unknown rendering engine found');
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
