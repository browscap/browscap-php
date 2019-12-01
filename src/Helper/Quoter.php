<?php
declare(strict_types = 1);

namespace BrowscapPHP\Helper;

/**
 * class to help quoting strings for using a regex
 */
final class Quoter implements QuoterInterface
{
    /**
     * Converts browscap match patterns into preg match patterns.
     *
     * @param string $user_agent
     * @param string $delimiter
     *
     * @return string
     */
    public function pregQuote(string $user_agent, string $delimiter = '/') : string
    {
        $pattern = preg_quote($user_agent, $delimiter);

        // the \\x replacement is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
        return str_replace(['\*', '\?', '\\x'], ['.*', '.', '\\\\x'], $pattern);
    }

    /**
     * Reverts the quoting of a pattern.
     *
     * @param  string $pattern
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    public function pregUnQuote(string $pattern) : string
    {
        // Fast check, because most parent pattern like 'DefaultProperties' don't need a replacement
        if (!preg_match('/[^a-z\s]/i', $pattern)) {
            return $pattern;
        }

        $origPattern = $pattern;

        // Undo the \\x replacement, that is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
        // @source https://github.com/browscap/browscap-php
        $pattern = preg_replace(
            ['/(?<!\\\\)\\.\\*/', '/(?<!\\\\)\\./', '/(?<!\\\\)\\\\x/'],
            ['\\*', '\\?', '\\x'],
            $pattern
        );

        if (null === $pattern) {
            throw new \UnexpectedValueException(
                sprintf('an error occured while handling pattern %s', $origPattern)
            );
        }

        // Undo preg_quote
        return str_replace(
            [
                '\\\\', '\\+', '\\*', '\\?', '\\[', '\\^', '\\]', '\\$', '\\(', '\\)', '\\{', '\\}', '\\=',
                '\\!', '\\<', '\\>', '\\|', '\\:', '\\-', '\\.', '\\/',
            ],
            [
                '\\', '+', '*', '?', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|', ':',
                '-', '.', '/',
            ],
            $pattern
        );
    }
}
