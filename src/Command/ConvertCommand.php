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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use phpbrowscap\Helper\Converter;
use phpbrowscap\Helper\LoggerHelper;
use phpbrowscap\Cache\BrowscapCache;

/**
 * commands to a downloaded Browscap ini file into a array
 *
 * @author Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 */
class ConvertCommand extends Command
{
    /**
     * @var string
     */
    private $resourceDirectory;

    /**
     * @var string
     */
    private $defaultIniFile;

    /**
     * @param string $resourceDirectory
     * @param string $defaultIniFile
     */
    public function __construct($resourceDirectory, $defaultIniFile)
    {
        parent::__construct();

        $this->resourceDirectory = $resourceDirectory;
        $this->defaultIniFile    = $defaultIniFile;
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
                'no-backup',
                null,
                InputOption::VALUE_NONE,
                'Do not backup the previously existing file'
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
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loggerHelper = new LoggerHelper();
        $logger       = $loggerHelper->create($input->getOption('debug'));
        
        $logger->info('initializing converting process');

        ini_set('memory_limit', '256M');
        $converter = new Converter($this->resourceDirectory);
        $converter->setLogger($logger);
        
        $cacheAdapter = new \WurflCache\Adapter\File(array(\WurflCache\Adapter\File::DIR => $this->resourceDirectory));
        $cache        = new BrowscapCache($cacheAdapter);
        
        $converter->setCache($cache);
        
        $logger->info('started converting local file');
        
        $file = ($input->getArgument('file') ? $input->getArgument('file') : ($this->defaultIniFile));
        
        $converter->convertFile($file, $input->getOption('no-backup'));
        
        $logger->info('finished converting local file');
    }
}