<?php
declare(strict_types = 1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Exception\ErrorCachedVersionException;
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Helper\Exception;
use BrowscapPHP\Helper\IniLoaderInterface;
use BrowscapPHP\Helper\LoggerHelper;
use Doctrine\Common\Cache\FilesystemCache;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;
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

    /**
     * @var string
     */
    private $defaultCacheFolder;

    public function __construct(string $defaultCacheFolder, string $defaultIniFile)
    {
        $this->defaultCacheFolder = $defaultCacheFolder;
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
                'browscap.ini file to download from remote location (possible values are: ' . IniLoaderInterface::PHP_INI_LITE
                . ', ' . IniLoaderInterface::PHP_INI . ', ' . IniLoaderInterface::PHP_INI_FULL . ')',
                IniLoaderInterface::PHP_INI
            )
            ->addOption(
                'cache',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Where the cache files are located',
                $this->defaultCacheFolder
            );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $logger = LoggerHelper::createDefaultLogger($output);

        /** @var string $cacheOption */
        $cacheOption = $input->getOption('cache');
        $fileCache = new FilesystemCache($cacheOption);
        $cache = new SimpleCacheAdapter($fileCache);

        /** @var string $file */
        $file = $input->getArgument('file');
        if (! $file) {
            $file = $this->defaultIniFile;
        }

        $output->writeln(sprintf('write fetched file to %s', $file));

        $logger->info('started fetching remote file');

        $browscap = new BrowscapUpdater($cache, $logger);

        /** @var string $remoteFileOption */
        $remoteFileOption = $input->getOption('remote-file');

        try {
            $browscap->fetch($file, $remoteFileOption);
        } catch (ErrorCachedVersionException $e) {
            $logger->debug($e);

            return 3;
        } catch (FetcherException $e) {
            $logger->debug($e);

            return 9;
        } catch (Exception $e) {
            $logger->debug($e);

            return 10;
        }

        $logger->info('finished fetching remote file');

        return 0;
    }
}
