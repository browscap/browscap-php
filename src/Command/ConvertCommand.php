<?php
declare(strict_types=1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Helper\LoggerHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WurflCache\Adapter\File;

/**
 * Command to convert a downloaded Browscap ini file and write it to the cache
 */
final class ConvertCommand extends Command
{
    /**
     * @var ?BrowscapCacheInterface
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

    public function __construct(
        string $defaultCacheFolder,
        string $defaultIniFile,
        ?BrowscapCacheInterface $cache = null
    ) {
        $this->defaultCacheFolder = $defaultCacheFolder;
        $this->defaultIniFile = $defaultIniFile;
        $this->cache = $cache;

        parent::__construct();
    }

    protected function configure() : void
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
        $logger       = $loggerHelper->create($input->getOption('debug'));

        $logger->info('initializing converting process');

        $browscap = new BrowscapUpdater();

        $browscap->setLogger($logger);
        $browscap->setCache($this->getCache($input));

        $logger->info('started converting local file');

        $file = ($input->getArgument('file') ? $input->getArgument('file') : ($this->defaultIniFile));

        $browscap->convertFile($file);

        $logger->info('finished converting local file');
    }

    private function getCache(InputInterface $input) : BrowscapCacheInterface
    {
        if (null === $this->cache) {
            $cacheAdapter = new File([File::DIR => $input->getOption('cache')]);
            $this->cache  = new BrowscapCache($cacheAdapter);
        }

        return $this->cache;
    }
}
