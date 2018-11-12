<?php
declare(strict_types = 1);

namespace BrowscapPHP\Helper;

/**
 * class to help quoting strings for using a regex
 */
interface QuoterInterface
{
    /**
     * Converts browscap match patterns into preg match patterns.
     *
     * @param string $user_agent
     * @param string $delimiter
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    public function pregQuote(string $user_agent, string $delimiter = '/') : string;

    /**
     * Reverts the quoting of a pattern.
     *
     * @param  string $pattern
     *
     * @return string
     */
    public function pregUnQuote(string $pattern) : string;
}
