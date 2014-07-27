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
use Symfony\Component\Filesystem\Filesystem;
use phpbrowscap\Helper\Fetcher;
use phpbrowscap\Helper\LoggerHelper;

/**
 * commands to fetch a browscap ini file from the remote host and store the content in a local file
 *
 * @author Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 */
class FetchCommand extends Command
{
    /**
     * @var string
     */
    private $defaultIniFile;

    /**
     * @param string $defaultIniFile
     */
    public function __construct($defaultIniFile)
    {
        parent::__construct();

        $this->defaultIniFile = $defaultIniFile;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('browscap:fetch')
            ->setDescription('Fetches an updated INI file for Browscap.')
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'browscap.ini file',
                $this->defaultIniFile
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
        
        $logger->info('initializing fetching process');

        $fs      = new Filesystem();
        $fetcher = new Fetcher();
        
        $logger->info('started fetching remote file');
        
        $content = $fetcher->fetch();
        
        $logger->info('finished fetching remote file');
        $logger->info('started storing remote file into local file');
        
        $file = ($input->getArgument('file') ? $input->getArgument('file') : ($this->defaultIniFile));
        
        $fs->dumpFile($file, $content);
        
        $logger->info('finished storing remote file into local file');
    }
}