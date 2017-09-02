<?php
declare(strict_types = 1);

namespace BrowscapPHP\Util\Logfile;

use BrowscapPHP\Exception\ReaderException;

/**
 * abstract parent class for all readers
 */
abstract class AbstractReader implements ReaderInterface
{
    /**
     * @param string $line
     *
     * @return bool
     */
    public function test(string $line) : bool
    {
        $matches = $this->match($line);

        return isset($matches['userAgentString']);
    }

    /**
     * @param string $line
     *
     * @throws \BrowscapPHP\Exception\ReaderException
     *
     * @return string
     */
    public function read(string $line) : string
    {
        $matches = $this->match($line);

        if (! isset($matches['userAgentString'])) {
            throw ReaderException::userAgentParserError($line);
        }

        return $matches['userAgentString'];
    }

    /**
     * @param string $line
     *
     * @return array
     */
    protected function match(string $line) : array
    {
        $matches = [];

        if (preg_match($this->getRegex(), $line, $matches)) {
            return $matches;
        }

        return [];
    }

    /**
     * @return string
     */
    abstract protected function getRegex() : string;
}
