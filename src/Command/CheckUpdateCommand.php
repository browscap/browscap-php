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
            ->setName('browscap:check-update')
            ->setDescription('Checks if an updated INI file is available.')
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
        $cache     = new SimpleCacheAdapter($fileCache);

        $logger->debug('started checking for new version of remote file');

        $browscap = new BrowscapUpdater($cache, $logger);
        $browscap->checkUpdate();

        $logger->debug('finished checking for new version of remote file');
    }
}
