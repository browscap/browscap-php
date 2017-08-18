<?php
declare(strict_types = 1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Helper\LoggerHelper;
use Doctrine\Common\Cache\FilesystemCache;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to convert a downloaded Browscap ini file and write it to the cache
 */
class ConvertCommand extends Command
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

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        string $defaultCacheFolder,
        string $defaultIniFile,
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null
    ) {
        $this->defaultCacheFolder = $defaultCacheFolder;
        $this->defaultIniFile = $defaultIniFile;
        $this->cache = $cache;
        $this->logger = $logger;

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
        $logger = $this->getLogger($input);

        $logger->info('initializing converting process');

        $browscap = new BrowscapUpdater($this->getCache($input), $logger);

        $logger->info('started converting local file');

        $file = ($input->getArgument('file') ? $input->getArgument('file') : ($this->defaultIniFile));

        $browscap->convertFile($file);

        $logger->info('finished converting local file');
    }

    private function getCache(InputInterface $input) : CacheInterface
    {
        if (null === $this->cache) {
            $fileCache = new FilesystemCache($input->getOption('cache'));
            $this->cache = new SimpleCacheAdapter($fileCache);
        }

        return $this->cache;
    }

    private function getLogger(InputInterface $input) : LoggerInterface
    {
        return LoggerHelper::createDefaultLogger($input->getOption('debug'));
    }
}
