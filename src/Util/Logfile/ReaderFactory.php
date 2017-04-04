<?php
declare(strict_types=1);

namespace BrowscapPHP\Util\Logfile;

/**
 * abstract parent class for all readers
 */
final class ReaderFactory
{
    /**
     * @var ReaderInterface[]
     */
    private static $readers = [];

    /**
     * @return \BrowscapPHP\Util\Logfile\ReaderCollection
     */
    public static function factory() : ReaderCollection
    {
        $collection = new ReaderCollection();

        foreach (self::getReaders() as $reader) {
            $collection->addReader($reader);
        }

        return $collection;
    }

    /**
     * @return \BrowscapPHP\Util\Logfile\ReaderInterface[]
     */
    private static function getReaders() : array
    {
        if (self::$readers) {
            return self::$readers;
        }

        self::$readers[] = new ApacheCommonLogFormatReader();

        return self::$readers;
    }
}
