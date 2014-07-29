<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace phpbrowscap\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use phpbrowscap\Helper\Converter;
use phpbrowscap\Helper\Fetcher;
use phpbrowscap\Helper\LoggerHelper;
use FileLoader\Loader;
use phpbrowscap\Exception\FetcherException;
use phpbrowscap\Helper\IniLoader;

/**
 * command to fetch a browscap ini file from the remote host, convert it into an array and store the content in a local
 * file
 *
 * @author Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 */
class UpdateCommand extends Command
{
    /**
     * @var string
     */
    private $resourceDirectory = null;

    /**
     * @param string $resourceDirectory
     */
    public function __construct($resourceDirectory)
    {
        $this->resourceDirectory = $resourceDirectory;

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('browscap:update')
            ->setDescription('Fetches an updated INI file for Browscap and overwrites the current PHP file.')
            ->addOption(
                'no-backup',
                null,
                InputOption::VALUE_NONE,
                'Do not backup the previously existing file'
            )
            ->addOption(
                'debug', 
                'd', 
                InputOption::VALUE_NONE, 
                'Should the debug mode entered?'
            )
        ;
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
        
        $logger->info('started updating cache with remote file');
        
        $level = error_reporting(0);

        $cacheAdapter = new \WurflCache\Adapter\File(array(\WurflCache\Adapter\File::DIR => $this->resourceDirectory));
        $cache        = new BrowscapCache($cacheAdapter);
        
        $browscap = new Browscap();
        
        $browscap
            ->setLogger($logger)
            ->setCache($cache)
            ->update(IniLoader::PHP_INI)
        ;

        error_reporting($level);
        
        $logger->info('finished updating cache with remote file');
    }
}
