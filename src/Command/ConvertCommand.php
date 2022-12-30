<?php

declare(strict_types=1);

namespace BrowscapPHP\Command;

use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Exception;
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
 * Command to convert a downloaded Browscap ini file and write it to the cache
 *
 * @internal This extends Symfony API, and we do not want to expose upstream BC breaks, so we DO NOT promise BC on this
 */
class ConvertCommand extends Command
{
    public const FILENAME_MISSING   = 6;
    public const FILE_NOT_FOUND     = 7;
    public const ERROR_READING_FILE = 8;

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
            ->setName('browscap:convert')
            ->setDescription('Converts an existing browscap.ini file to a cache.php file.')
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Path to the browscap.ini file',
                $this->defaultIniFile,
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

        $logger->info('initializing converting process');

        $browscap = new BrowscapUpdater($cache, $logger);

        $logger->info('started converting local file');

        $file = $input->getArgument('file');
        assert(is_string($file));
        if (! $file) {
            $file = $this->defaultIniFile;
        }

        if ($file === null) {
            return self::FILENAME_MISSING;
        }

        $output->writeln(sprintf('converting file %s', $file));

        try {
            $browscap->convertFile($file);
        } catch (Exception\FileNameMissingException $e) {
            $logger->debug($e);

            return self::FILENAME_MISSING;
        } catch (Exception\FileNotFoundException $e) {
            $logger->debug($e);

            return self::FILE_NOT_FOUND;
        } catch (Exception\ErrorReadingFileException $e) {
            $logger->debug($e);

            return self::ERROR_READING_FILE;
        } catch (Throwable $e) {
            $logger->info($e);

            return CheckUpdateCommand::GENERIC_ERROR;
        }

        $logger->info('finished converting local file');

        return self::SUCCESS;
    }
}
