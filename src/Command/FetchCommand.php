<?php
declare(strict_types=1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Helper\IniLoader;
use BrowscapPHP\Helper\LoggerHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to fetch a browscap ini file from the remote host and store the content in a local file
 */
class FetchCommand extends Command
{
    /**
     * @var string
     */
    private $defaultIniFile;

    public function __construct(string $defaultIniFile)
    {
        $this->defaultIniFile = $defaultIniFile;

        parent::__construct();
    }

    protected function configure() : void
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
                'remote-file',
                'r',
                InputOption::VALUE_OPTIONAL,
                'browscap.ini file to download from remote location (possible values are: ' . IniLoader::PHP_INI_LITE
                . ', ' . IniLoader::PHP_INI . ', ' . IniLoader::PHP_INI_FULL . ')',
                IniLoader::PHP_INI
            )
            ->addOption(
                'debug',
                'd',
                InputOption::VALUE_NONE,
                'Should the debug mode entered?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $loggerHelper = new LoggerHelper();
        $logger       = $loggerHelper->create($input->getOption('debug'));

        $file = $input->getArgument('file');
        if (!$file) {
            $file = $this->defaultIniFile;
        }

        $logger->info('started fetching remote file');

        $browscap = new BrowscapUpdater();

        $browscap->setLogger($logger);
        $browscap->fetch($file, $input->getOption('remote-file'));

        $logger->info('finished fetching remote file');
    }
}
