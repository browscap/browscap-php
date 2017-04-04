<?php
declare(strict_types=1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Cache\BrowscapCache;
use BrowscapPHP\Cache\BrowscapCacheInterface;
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
class CheckUpdateCommand extends Command
{
    /**
     * @var ?BrowscapCacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $defaultCacheFolder;

    public function __construct(string $defaultCacheFolder, ?BrowscapCacheInterface $cache = null)
    {
        $this->defaultCacheFolder = $defaultCacheFolder;
        $this->cache = $cache;

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
        $loggerHelper = new LoggerHelper();
        $logger = $loggerHelper->create($input->getOption('debug'));

        $logger->debug('started checking for new version of remote file');

        $browscap = new BrowscapUpdater();

        $browscap->setLogger($logger);
        $browscap->setCache($this->getCache($input));
        $browscap->checkUpdate();

        $logger->debug('finished checking for new version of remote file');
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
