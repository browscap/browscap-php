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

    private $uas = array();

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

        $browscap = $this->getBrowscap();
        $loader   = new IniLoader();

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
            $collection     = ReaderFactory::factory();

            $logger->info('Analyzing file "' . $file->getPathname() . '"');

            if ($internalLoader->isSupportingLoadingLines()) {
                if (!$internalLoader->init($path)) {
                    $logger->info('Skipping empty file "' . $file->getPathname() . '"');
                    continue;
                }

                $countOk    = 0;
                $countNok   = 0;
                $totalCount = 1;

                while ($internalLoader->isValid()) {
                    try {
                        $this->handleLine(
                            $collection,
                            $browscap,
                            $internalLoader->getLine()
                        );

                        $this->outputProgress($output, '.', $totalCount, $countOk, $countNok);
                        $countOk++;
                    } catch (ReaderException $e) {
                        $this->outputProgress($output, 'E', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (UnknownBrowserTypeException $e) {
                        $this->outputProgress($output, 'T', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (UnknownBrowserException $e) {
                        $this->outputProgress($output, 'B', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (UnknownPlatformException $e) {
                        $this->outputProgress($output, 'P', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (UnknownDeviceException $e) {
                        $this->outputProgress($output, 'D', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (UnknownEngineException $e) {
                        $this->outputProgress($output, 'N', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (\Exception $e) {
                        $this->outputProgress($output, 'U', $totalCount, $countOk, $countNok);
                        $countNok++;
                    }

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

                $countOk    = 0;
                $countNok   = 0;
                $totalCount = count($lines);

                foreach ($lines as $line) {
                    try {
                        $this->handleLine(
                            $collection,
                            $browscap,
                            $line
                        );

                        $this->outputProgress($output, '.', $totalCount, $countOk, $countNok);
                        $countOk++;
                    } catch (ReaderException $e) {
                        $this->outputProgress($output, 'E', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (UnknownBrowserTypeException $e) {
                        $this->outputProgress($output, 'T', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (UnknownBrowserException $e) {
                        $this->outputProgress($output, 'B', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (UnknownPlatformException $e) {
                        $this->outputProgress($output, 'P', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (UnknownDeviceException $e) {
                        $this->outputProgress($output, 'D', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (UnknownEngineException $e) {
                        $this->outputProgress($output, 'N', $totalCount, $countOk, $countNok);
                        $countNok++;
                    } catch (\Exception $e) {
                        $this->outputProgress($output, 'U', $totalCount, $countOk, $countNok);
                        $countNok++;
                    }
                }
            }

            $this->outputProgress($output, '', $totalCount, $countOk, $countNok, true);

            arsort($this->uas, SORT_NUMERIC);

            foreach ($this->uas as $agentOfLine => $count) {
                $sql = "INSERT INTO `agents` (`agent`, `count`) VALUES ('" . addslashes($agentOfLine) . "', " . addslashes($count) . ") ON DUPLICATE KEY UPDATE `count`=`count`+" . addslashes($count) . ";\n";
                file_put_contents($input->getArgument('output') . '/output.sql', $sql, FILE_APPEND | LOCK_EX);
            }

            $fs         = new Filesystem();
            $content    = implode(PHP_EOL, array_unique($this->undefinedClients));
            $outputFile = $input->getArgument('output') . '/output.txt';

            try {
                $fs->dumpFile($outputFile, $content);
            } catch (IOException $e) {
                // do nothing
            }
        }

        $fs         = new Filesystem();
        $content    = implode(PHP_EOL, array_unique($this->undefinedClients));
        $outputFile = $input->getArgument('output') . '/output.txt';

        try {
            $fs->dumpFile($outputFile, $content);
        } catch (IOException $e) {
            throw new \UnexpectedValueException('writing to file "' . $outputFile . '" failed', 0, $e);
        }
    }

    /**
     * @param \phpbrowscap\Util\Logfile\ReaderCollection $collection
     * @param \phpbrowscap\Browscap                      $browscap
     * @param integer                                    $line
     *
     * @throws UnknownBrowserException
     * @throws UnknownBrowserTypeException
     * @throws UnknownDeviceException
     * @throws UnknownEngineException
     * @throws UnknownPlatformException
     * @throws \Exception
     */
    private function handleLine(ReaderCollection $collection, Browscap $browscap, $line)
    {
        $userAgentString = $collection->read($line);

        if (isset($this->uas[$userAgentString])) {
            $this->uas[$userAgentString]++;
        } else {
            $this->uas[$userAgentString] = 1;
        }

        try {
            $this->getResult($browscap->getBrowser($userAgentString));
        } catch (\Exception $e) {
            $this->undefinedClients[] = $userAgentString;

            throw $e;
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string                                            $result
     * @param integer                                           $totalCount
     * @param                                                   integer $countOk
     * @param                                                   integer $countNok
     * @param bool                                              $end
     *
     * @return int
     */
    private function outputProgress(OutputInterface $output, $result, $totalCount, $countOk, $countNok, $end = false)
    {
        if (false === strpos(strtolower(PHP_OS), 'win')) {
            $rowLength = max(70, exec('tput cols') - 30);
        } else {
            $rowLength = 70;
        }

        if (($totalCount % $rowLength) === 0 || $end) {
            $formatString = '  %s OK, %s NOK, Summary %s';
            $result       = $end ? str_pad($result, $rowLength - ($countOk % $rowLength), ' ', STR_PAD_RIGHT) : $result;
            $output->writeln($result . sprintf($formatString, $countOk, $countNok, $totalCount));

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
        if ($result->browser_type === 'unknown') {
            throw new UnknownBrowserTypeException('unknwon browser type found');
        }

        if ($result->browser_type === 'Bot/Crawler') {
            return '.';
        }

        if ($result->browser === 'Default Browser') {
            throw new UnknownBrowserException('unknwon browser found');
        }

        if ($result->platform === 'unknown') {
            throw new UnknownPlatformException('unknown platform found');
        }

        if ($result->device_type === 'unknown') {
            throw new UnknownDeviceException('unknwon device type found');
        }

        if ($result->renderingengine_name === 'unknown') {
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
