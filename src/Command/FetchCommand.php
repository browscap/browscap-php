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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use phpbrowscap\Helper\Fetcher;

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
        $fs      = new Filesystem();
        $fetcher = new Fetcher();
        $fs->dumpFile($input->getArgument('file'), $fetcher->fetch());
    }
}
