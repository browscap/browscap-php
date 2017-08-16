<?php
declare(strict_types = 1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Helper\IniLoader;
use BrowscapPHP\Helper\IniLoaderInterface;
use BrowscapPHP\Helper\LoggerHelper;
use Psr\SimpleCache\CacheInterface;
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
     * @var ?CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $defaultIniFile;

    /**
     * @var string
     */
    private $defaultCacheFolder;

    public function __construct(string $defaultCacheFolder, string $defaultIniFile, ?CacheInterface $cache = null)
    {
        $this->defaultCacheFolder = $defaultCacheFolder;
        $this->defaultIniFile = $defaultIniFile;
        $this->cache = $cache;

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
                'browscap.ini file to download from remote location (possible values are: ' . IniLoaderInterface::PHP_INI_LITE
                . ', ' . IniLoaderInterface::PHP_INI . ', ' . IniLoaderInterface::PHP_INI_FULL . ')',
                IniLoaderInterface::PHP_INI
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
        $logger = LoggerHelper::createDefaultLogger($input->getOption('debug'));

        $file = $input->getArgument('file');
        if (! $file) {
            $file = $this->defaultIniFile;
        }

        $logger->info('started fetching remote file');

        $browscap = new BrowscapUpdater($this->getCache($input), $logger);
        $browscap->fetch($file, $input->getOption('remote-file'));

        $logger->info('finished fetching remote file');
    }

    private function getCache(InputInterface $input) : CacheInterface
    {
        if (null === $this->cache) {
            $cacheAdapter = new File([File::DIR => $input->getOption('cache')]);
            $this->cache = new CacheInterface($cacheAdapter);
        }

        return $this->cache;
    }
}
