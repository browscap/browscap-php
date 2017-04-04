<?php
declare(strict_types=1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Helper\IniLoader;
use BrowscapPHP\Helper\LoggerHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WurflCache\Adapter\File;

/**
 * Command to fetch a browscap ini file from the remote host, convert it into an array and store the content in a local
 * file
 */
class UpdateCommand extends Command
{
    /**
     * @var ?BrowscapCacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $defaultCacheFolder;

    public function __construct($defaultCacheFolder, ?BrowscapCacheInterface $cache = null)
    {
        $this->defaultCacheFolder = $defaultCacheFolder;
        $this->cache = $cache;

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
                'browscap.ini file to download from remote location (possible values are: ' . IniLoader::PHP_INI_LITE
                . ', ' . IniLoader::PHP_INI . ', ' . IniLoader::PHP_INI_FULL . ')',
                IniLoader::PHP_INI
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
        $loggerHelper = new LoggerHelper();
        $logger = $loggerHelper->create($input->getOption('debug'));

        $logger->info('started updating cache with remote file');

        $browscap = new BrowscapUpdater();

        $browscap->setLogger($logger);
        $browscap->setCache($this->getCache($input));
        $browscap->update($input->getOption('remote-file'));

        $logger->info('finished updating cache with remote file');
    }

    private function getCache(InputInterface $input) : BrowscapCacheInterface
    {
        if (null === $this->cache) {
            $cacheAdapter = new File([File::DIR => $input->getOption('cache')]);
            $this->cache = new BrowscapCache($cacheAdapter);
        }

        return $this->cache;
    }
}
