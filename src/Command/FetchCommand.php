<?php

declare(strict_types=1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Exception\ErrorCachedVersionException;
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Helper\IniLoaderInterface;
use BrowscapPHP\Helper\LoggerHelper;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MatthiasMullie\Scrapbook\Adapters\Flysystem;
use MatthiasMullie\Scrapbook\Psr16\SimpleCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function assert;
use function is_string;
use function sprintf;

/**
 * Command to fetch a browscap ini file from the remote host and store the content in a local file
 *
 * @internal This extends Symfony API, and we do not want to expose upstream BC breaks, so we DO NOT promise BC on this
 */
class FetchCommand extends Command
{
    private ?string $defaultIniFile = null;

    private ?string $defaultCacheFolder = null;

    /** @throws LogicException */
    public function __construct(string $defaultCacheFolder, string $defaultIniFile)
    {
        $this->defaultCacheFolder = $defaultCacheFolder;
        $this->defaultIniFile     = $defaultIniFile;

        parent::__construct();
    }

    /** @throws InvalidArgumentException */
    protected function configure(): void
    {
        $this
            ->setName('browscap:fetch')
            ->setDescription('Fetches an updated INI file for Browscap.')
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'browscap.ini file',
                $this->defaultIniFile,
            )
            ->addOption(
                'remote-file',
                'r',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'browscap.ini file to download from remote location (possible values are: %s, %s, %s)',
                    IniLoaderInterface::PHP_INI_LITE,
                    IniLoaderInterface::PHP_INI,
                    IniLoaderInterface::PHP_INI_FULL,
                ),
                IniLoaderInterface::PHP_INI,
            )
            ->addOption(
                'cache',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Where the cache files are located',
                $this->defaultCacheFolder,
            );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = LoggerHelper::createDefaultLogger($output);

        $cacheOption = $input->getOption('cache');
        assert(is_string($cacheOption));

        $adapter    = new LocalFilesystemAdapter($cacheOption);
        $filesystem = new Filesystem($adapter);
        $cache      = new SimpleCache(
            new Flysystem($filesystem),
        );

        $file = $input->getArgument('file');
        assert(is_string($file));
        if (! $file) {
            $file = $this->defaultIniFile;
        }

        if ($file === null) {
            return ConvertCommand::FILENAME_MISSING;
        }

        $output->writeln(sprintf('write fetched file to %s', $file));

        $logger->info('started fetching remote file');

        $browscap = new BrowscapUpdater($cache, $logger);

        $remoteFileOption = $input->getOption('remote-file');
        assert(is_string($remoteFileOption));

        try {
            $browscap->fetch($file, $remoteFileOption);
        } catch (ErrorCachedVersionException $e) {
            $logger->debug($e);

            return CheckUpdateCommand::ERROR_READING_CACHE;
        } catch (FetcherException $e) {
            $logger->debug($e);

            return CheckUpdateCommand::ERROR_READING_REMOTE_FILE;
        } catch (Throwable $e) {
            $logger->info($e);

            return CheckUpdateCommand::GENERIC_ERROR;
        }

        $logger->info('finished fetching remote file');

        return self::SUCCESS;
    }
}
