<?php

declare(strict_types=1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Exception\ErrorCachedVersionException;
use BrowscapPHP\Exception\FetcherException;
use BrowscapPHP\Exception\NoCachedVersionException;
use BrowscapPHP\Exception\NoNewVersionException;
use BrowscapPHP\Helper\LoggerHelper;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MatthiasMullie\Scrapbook\Adapters\Flysystem;
use MatthiasMullie\Scrapbook\Psr16\SimpleCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function assert;
use function is_string;

/**
 * Command to fetch a browscap ini file from the remote host, convert it into an array and store the content in a local
 * file
 *
 * @internal This extends Symfony API, and we do not want to expose upstream BC breaks, so we DO NOT promise BC on this
 */
class CheckUpdateCommand extends Command
{
    public const NO_CACHED_VERSION         = 1;
    public const NO_NEWER_VERSION          = 2;
    public const ERROR_READING_CACHE       = 3;
    public const ERROR_READING_REMOTE_FILE = 4;
    public const GENERIC_ERROR             = 5;

    private ?string $defaultCacheFolder = null;

    /** @throws LogicException */
    public function __construct(string $defaultCacheFolder)
    {
        $this->defaultCacheFolder = $defaultCacheFolder;

        parent::__construct();
    }

    /** @throws InvalidArgumentException */
    protected function configure(): void
    {
        $this
            ->setName('browscap:check-update')
            ->setDescription('Checks if an updated INI file is available.')
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

        $logger->debug('started checking for new version of remote file');

        $browscap = new BrowscapUpdater($cache, $logger);

        try {
            $browscap->checkUpdate();
        } catch (NoCachedVersionException $e) {
            return self::NO_CACHED_VERSION;
        } catch (NoNewVersionException $e) {
            // no newer version available
            $logger->info('there is no newer version available');

            return self::NO_NEWER_VERSION;
        } catch (ErrorCachedVersionException $e) {
            $logger->info($e);

            return self::ERROR_READING_CACHE;
        } catch (FetcherException $e) {
            $logger->info($e);

            return self::ERROR_READING_REMOTE_FILE;
        } catch (Throwable $e) {
            $logger->info($e);

            return self::GENERIC_ERROR;
        }

        $logger->debug('finished checking for new version of remote file');

        return self::SUCCESS;
    }
}
