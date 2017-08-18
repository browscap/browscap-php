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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to fetch a browscap ini file from the remote host, convert it into an array and store the content in a local
 * file
 */
class CheckUpdateCommand extends Command
{
    /**
     * @var ?CacheInterface
     */
    private $cache;

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
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null
    ) {
        $this->defaultCacheFolder = $defaultCacheFolder;
        $this->cache = $cache;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setName('browscap:check-update')
            ->setDescription('Checks if an updated INI file is available.')
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

        $logger->debug('started checking for new version of remote file');

        $browscap = new BrowscapUpdater($this->getCache($input), $logger);
        $browscap->checkUpdate();

        $logger->debug('finished checking for new version of remote file');
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
        if (null === $this->logger) {
            $this->logger = LoggerHelper::createDefaultLogger($input->getOption('debug'));
        }

        return $this->logger;
    }
}
