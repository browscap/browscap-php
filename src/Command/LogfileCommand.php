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
use UAParser\Util\Logfile\AbstractReader;
use phpbrowscap\Helper\LoggerHelper;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('log-file') && !$input->getOption('log-dir')) {
            throw InvalidArgumentException::oneOfCommandArguments('log-file', 'log-dir');
        }

        $loggerHelper = new LoggerHelper();
        $logger       = $loggerHelper->create($input->getOption('debug'));

        $loggerHelper = new LoggerHelper();
        $logger       = $loggerHelper->create($input->getOption('debug'));

        $cacheAdapter = new \WurflCache\Adapter\File(array(\WurflCache\Adapter\File::DIR => $this->resourceDirectory));
        $cache        = new BrowscapCache($cacheAdapter);

        $browscap = new Browscap();

        $browscap
            ->setLogger($logger)
            ->setCache($cache)
        ;

        $undefinedClients = array();
        /** @var $file SplFileInfo */
        foreach ($this->getFiles($input) as $file) {

            $path = $this->getPath($file);
            $lines = file($path);

            if (empty($lines)) {
                $output->writeln(sprintf('Skipping empty file "%s"', $file->getPathname()));
                $output->writeln('');
                continue;
            }

            $firstLine = reset($lines);

            $reader = AbstractReader::factory($firstLine);
            if (!$reader) {
                $output->writeln(sprintf('Could not find reader for file "%s"', $file->getPathname()));
                $output->writeln('');
                continue;
            }

            $output->writeln('');
            $output->writeln(sprintf('Analyzing "%s"', $file->getPathname()));

            $count = 1;
            $totalCount = count($lines);
            foreach ($lines as $line) {

                try {
                    $userAgentString = $reader->read($line);
                } catch (ReaderException $e) {
                    $count = $this->outputProgress($output, 'E', $count, $totalCount);
                    continue;
                }

                $result = $this->getResult($browscap->getBrowser($userAgentString));
                if ($result !== '.') {
                    $undefinedClients[] = $userAgentString;
                }

                $count = $this->outputProgress($output, $result, $count, $totalCount);
            }
            $this->outputProgress($output, '', $count - 1, $totalCount, true);
            $output->writeln('');
        }

        $undefinedClients = $this->filter($undefinedClients);

        $fs = new Filesystem();
        $fs->dumpFile($input->getArgument('output'), join(PHP_EOL, $undefinedClients));
    }

    /**
     * @param string $result
     * @param integer $count
     * @param integer $totalCount
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

    private function getResult(\stdClass $result)
    {
        if ($result->browser_type === 'Bot/Crawler') {
            return '.';
        } elseif ($result->browser === 'Default Browser') {
            return 'B';
        } elseif ($result->platform === 'unknown') {
            return 'P';
        } elseif ($result->device_type === 'unknown') {
            return 'M';
        }

        return '.';
    }

    /**
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

    private function filter(array $lines)
    {
        return array_unique($lines);
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
}
