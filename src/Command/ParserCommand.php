<?php
declare(strict_types = 1);

namespace BrowscapPHP\Command;

use BrowscapPHP\Browscap;
use BrowscapPHP\Exception;
use BrowscapPHP\Helper\LoggerHelper;
use Doctrine\Common\Cache\FilesystemCache;
use ExceptionalJSON\EncodeErrorException;
use Roave\DoctrineSimpleCache\SimpleCacheAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * commands to parse a given useragent
 */
class ParserCommand extends Command
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
            ->setName('browscap:parse')
            ->setDescription('Parses a user agent string and dumps the results.')
            ->addArgument(
                'user-agent',
                InputArgument::REQUIRED,
                'User agent string to analyze',
                null
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

        $browscap = new Browscap($cache, $logger);

        /** @var string $uaArgument */
        $uaArgument = $input->getArgument('user-agent');

        try {
            $result = $browscap->getBrowser($uaArgument);
        } catch (Exception $e) {
            $logger->debug($e);

            return 11;
        }

        try {
            $output->writeln(\ExceptionalJSON\encode($result, JSON_PRETTY_PRINT));
        } catch (EncodeErrorException $e) {
            $logger->error($e);

            return 11;
        }

        return 0;
    }
}
