<?php
declare(strict_types = 1);

namespace BrowscapPHP\Util\Logfile;

use BrowscapPHP\Exception\ReaderException;

/**
 * reader collection class
 */
final class ReaderCollection implements ReaderInterface
{
    /**
     * @var \BrowscapPHP\Util\Logfile\AbstractReader[]
     */
    private $readers = [];

    /**
     * adds a new reader to this collection
     *
     * @param \BrowscapPHP\Util\Logfile\ReaderInterface $reader
     *
     * @return \BrowscapPHP\Util\Logfile\ReaderCollection
     */
    public function addReader(ReaderInterface $reader)
    {
        $this->readers[] = $reader;

        return $this;
    }

    /**
     * @param string $line
     *
     * @return bool
     */
    public function test(string $line) : bool
    {
        foreach ($this->readers as $reader) {
            if ($reader->test($line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $line
     *
     * @throws \BrowscapPHP\Exception\ReaderException
     * @return string
     */
    public function read(string $line) : string
    {
        foreach ($this->readers as $reader) {
            if ($reader->test($line)) {
                return $reader->read($line);
            }
        }

        throw ReaderException::userAgentParserError($line);
    }
}
