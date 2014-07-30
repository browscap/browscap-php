<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Refer to the LICENSE file distributed with this package.
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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use phpbrowscap\Helper\Converter;
use phpbrowscap\Helper\LoggerHelper;
use phpbrowscap\Cache\BrowscapCache;

/**
 * command to convert a downloaded Browscap ini file and write it to the cache
 *
 * @category   Browscap-PHP
 * @package    Command
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
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
        
        $logger->info('initializing converting process');

        $cacheAdapter = new \WurflCache\Adapter\File(array(\WurflCache\Adapter\File::DIR => $this->resourceDirectory));
        $cache        = new BrowscapCache($cacheAdapter);
        
        $browscap = new Browscap();
        
        $browscap
            ->setLogger($logger)
            ->setCache($cache)
        ;
        
        $logger->info('started converting local file');
        
        $file = ($input->getArgument('file') ? $input->getArgument('file') : ($this->defaultIniFile));
        
        $browscap->convertFile($file, $input->getOption('no-backup'));
        
        $logger->info('finished converting local file');
    }
}
