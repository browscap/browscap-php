<?php
declare(strict_types = 1);

namespace BrowscapPHP\Command;

use BrowscapPHP\Browscap;
use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Helper\LoggerHelper;
use Psr\SimpleCache\CacheInterface;
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

    public function __construct(string $defaultCacheFolder, ?CacheInterface $cache = null)
    {
        $this->defaultCacheFolder = $defaultCacheFolder;
        $this->cache = $cache;

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
        $logger = LoggerHelper::createDefaultLogger($input->getOption('debug'));

        $browscap = new Browscap($this->getCache($input), $logger);

        $result = $browscap->getBrowser($input->getArgument('user-agent'));

        $output->writeln(json_encode($result, JSON_PRETTY_PRINT));
    }

    private function getCache(InputInterface $input) : CacheInterface
    {
        if (null === $this->cache) {
            $cacheAdapter = new File([File::DIR => $input->getOption('cache')]);
            $this->cache = new CacheInterface($cacheAdapter);
        }

        return $this->cache;
    }
}
