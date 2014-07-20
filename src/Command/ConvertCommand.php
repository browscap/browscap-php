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
    const DEFAULT_BUILD_FOLDER = '/../../build';

    /**
     * @var string
     */
    const DEFAULT_RESOURCES_FOLDER = '/../../resources';

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
            ->setDescription('Converts an existing regexes.yaml file to a regexes.php file.')
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Path to the regexes.yaml file',
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

        $converter = new Converter($this->resourceDirectory);
        
        $logger->info('started converting local file');

        $converter->convertFile($input->getArgument('file'), $input->getOption('no-backup'));
        
        $logger->info('finished converting local file');
    }
}
