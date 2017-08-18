<?php
declare(strict_types = 1);

namespace BrowscapPHP\Command;

use BrowscapPHP\Browscap;
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
 * commands to parse a given useragent
 */
class ParserCommand extends Command
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
            ->setName('browscap:parse')
            ->setDescription('Parses a user agent string and dumps the results.')
            ->addArgument(
                'user-agent',
                InputArgument::REQUIRED,
                'User agent string to analyze',
                null
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

        $browscap = new Browscap($this->getCache($input), $logger);

        $result = $browscap->getBrowser($input->getArgument('user-agent'));

        $output->writeln(json_encode($result, JSON_PRETTY_PRINT));
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
