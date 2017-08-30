<?php
declare(strict_types = 1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Helper\IniLoaderInterface;
use BrowscapPHP\Helper\LoggerHelper;
use Doctrine\Common\Cache\FilesystemCache;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to fetch a browscap ini file from the remote host, convert it into an array and store the content in a local
 * file
 */
class UpdateCommand extends Command
{
    /**
     * @var string
     */
    private $defaultCacheFolder;

    public function __construct(string $defaultCacheFolder)
    {
        $this->defaultCacheFolder = $defaultCacheFolder;

        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setName('browscap:update')
            ->setDescription('Fetches an updated INI file for Browscap and overwrites the current PHP file.')
            ->addOption(
                'remote-file',
                'r',
                InputOption::VALUE_OPTIONAL,
                'browscap.ini file to download from remote location (possible values are: ' . IniLoaderInterface::PHP_INI_LITE
                . ', ' . IniLoaderInterface::PHP_INI . ', ' . IniLoaderInterface::PHP_INI_FULL . ')',
                IniLoaderInterface::PHP_INI
            )
            ->addOption(
                'no-backup',
                null,
                InputOption::VALUE_NONE,
                'Do not backup the previously existing file'
            )
            ->addOption(
                'cache',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Where the cache files are located',
                $this->defaultCacheFolder
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $logger = LoggerHelper::createDefaultLogger($output);

        $fileCache = new FilesystemCache($input->getOption('cache'));
        $cache = new SimpleCacheAdapter($fileCache);

        $logger->info('started updating cache with remote file');

        $browscap = new BrowscapUpdater($cache, $logger);
        $browscap->update($input->getOption('remote-file'));

        $logger->info('finished updating cache with remote file');
    }
}
