<?php
declare(strict_types = 1);

namespace BrowscapPHP\Util\Logfile;

/**
 * abstract parent class for all readers
 */
final class ReaderFactory
{
    /**
     * @return \BrowscapPHP\Util\Logfile\ReaderCollection
     */
    public static function factory() : ReaderCollection
    {
        $collection = new ReaderCollection();

        $collection->addReader(new ApacheCommonLogFormatReader());

        return $collection;
    }
}
