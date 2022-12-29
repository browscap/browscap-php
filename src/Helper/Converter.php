<?php

declare(strict_types=1);

namespace BrowscapPHP\Helper;

use BrowscapPHP\Cache\BrowscapCacheInterface;
use BrowscapPHP\Exception\ErrorReadingFileException;
use BrowscapPHP\Exception\FileNotFoundException;
use BrowscapPHP\IniParser\IniParser;
use JsonException;
use OutOfRangeException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use UnexpectedValueException;

use function file_get_contents;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * patternHelper to convert the ini data, parses the data and stores them into the cache
 */
final class Converter implements ConverterInterface
{
    /**
     * The key to search for in the INI file to find the browscap settings
     */
    private const BROWSCAP_VERSION_KEY = 'GJK_Browscap_Version';

    private LoggerInterface $logger;

    /**
     * The cache instance
     */
    private BrowscapCacheInterface $cache;

    /**
     * a filesystem patternHelper instance
     */
    private Filesystem $filessystem;

    /**
     * version of the ini file
     */
    private int $iniVersion = 0;

    /** @throws void */
    public function __construct(LoggerInterface $logger, BrowscapCacheInterface $cache)
    {
        $this->logger      = $logger;
        $this->cache       = $cache;
        $this->filessystem = new Filesystem();
    }

    /**
     * Sets a filesystem instance
     *
     * @throws void
     */
    public function setFilesystem(Filesystem $file): void
    {
        $this->filessystem = $file;
    }

    /**
     * converts a file
     *
     * @throws FileNotFoundException
     * @throws ErrorReadingFileException
     */
    public function convertFile(string $iniFile): void
    {
        if (! $this->filessystem->exists($iniFile)) {
            throw FileNotFoundException::fileNotFound($iniFile);
        }

        $this->logger->info('start reading file');

        $iniString = file_get_contents($iniFile);

        $this->logger->info('finished reading file');

        if (! is_string($iniString)) {
            throw new ErrorReadingFileException(sprintf('could not read file %s', $iniFile));
        }

        $this->convertString($iniString);
    }

    /**
     * converts the string content
     *
     * @throws void
     */
    public function convertString(string $iniString): void
    {
        $iniParser = new IniParser();

        $this->logger->info('start creating patterns from the ini data');

        foreach ($iniParser->createPatterns($iniString) as $subkey => $content) {
            if ($subkey === '') {
                continue;
            }

            try {
                if (! $this->cache->setItem('browscap.patterns.' . $subkey, $content, true)) {
                    $this->logger->error('could not write pattern data "' . $subkey . '" to the cache');
                }
            } catch (InvalidArgumentException $e) {
                $this->logger->error(new \InvalidArgumentException('an error occured while writing pattern data into the cache', 0, $e));
            }
        }

        $this->logger->info('finished creating patterns from the ini data');

        $this->logger->info('start creating data from the ini data');

        try {
            foreach ($iniParser->createIniParts($iniString) as $subkey => $content) {
                if ($subkey === '') {
                    continue;
                }

                try {
                    if (! $this->cache->setItem('browscap.iniparts.' . $subkey, $content, true)) {
                        $this->logger->error('could not write property data "' . $subkey . '" to the cache');
                    }
                } catch (InvalidArgumentException $e) {
                    $this->logger->error(new \InvalidArgumentException('an error occured while writing property data into the cache', 0, $e));
                }
            }
        } catch (OutOfRangeException | UnexpectedValueException | JsonException | \InvalidArgumentException $e) {
            $this->logger->error(new \InvalidArgumentException('an error occured while writing property data into the cache', 0, $e));
        }

        try {
            $this->cache->setItem('browscap.releaseDate', $this->getIniReleaseDate($iniString), false);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(new \InvalidArgumentException('an error occured while writing data release date into the cache', 0, $e));
        }

        try {
            $this->cache->setItem('browscap.type', $this->getIniType($iniString), false);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(new \InvalidArgumentException('an error occured while writing the data type into the cache', 0, $e));
        }

        $this->logger->info('finished creating data from the ini data');
    }

    /**
     * Parses the ini data to get the version of loaded ini file
     *
     * @param string $iniString The loaded ini data
     *
     * @throws void
     */
    public function getIniVersion(string $iniString): int
    {
        $quoterHelper = new Quoter();
        $key          = $quoterHelper->pregQuote(self::BROWSCAP_VERSION_KEY);

        if (preg_match('/\.*\[' . $key . '\][^\[]*Version=(\d+)\D.*/', $iniString, $matches)) {
            if (isset($matches[1])) {
                $this->iniVersion = (int) $matches[1];
            }
        }

        return $this->iniVersion;
    }

    /**
     * sets the version
     *
     * @throws void
     */
    public function setVersion(int $version): void
    {
        $this->iniVersion = $version;
    }

    /**
     * stores the version of the ini file into cache
     *
     * @throws void
     */
    public function storeVersion(): void
    {
        try {
            $this->cache->setItem('browscap.version', $this->iniVersion, false);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(new \InvalidArgumentException('an error occured while writing the data version into the cache', 0, $e));
        }
    }

    /**
     * Parses the ini data to get the releaseDate of loaded ini file
     *
     * @param string $iniString The loaded ini data
     *
     * @throws void
     */
    private function getIniReleaseDate(string $iniString): ?string
    {
        if (preg_match('/Released=(.*)/', $iniString, $matches)) {
            if (isset($matches[1])) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Parses the ini data to get the releaseDate of loaded ini file
     *
     * @param string $iniString The loaded ini data
     *
     * @throws void
     */
    private function getIniType(string $iniString): ?string
    {
        if (preg_match('/Type=(.*)/', $iniString, $matches)) {
            if (isset($matches[1])) {
                return $matches[1];
            }
        }

        return null;
    }
}
