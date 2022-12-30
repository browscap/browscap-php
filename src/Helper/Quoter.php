<?php

declare(strict_types=1);

namespace BrowscapPHP\Helper;

use UnexpectedValueException;

use function preg_match;
use function preg_quote;
use function preg_replace;
use function sprintf;
use function str_replace;

/**
 * class to help quoting strings for using a regex
 */
final class Quoter implements QuoterInterface
{
    /**
     * Converts browscap match patterns into preg match patterns.
     *
     * @throws void
     */
    public function pregQuote(string $useragent, string $delimiter = '/'): string
    {
        $pattern = preg_quote($useragent, $delimiter);

        // the \\x replacement is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
        return str_replace(['\*', '\?', '\\x'], ['.*', '.', '\\\\x'], $pattern);
    }

    /**
     * Reverts the quoting of a pattern.
     *
     * @throws UnexpectedValueException
     */
    public function pregUnQuote(string $pattern): string
    {
        // Fast check, because most parent pattern like 'DefaultProperties' don't need a replacement
        if (! preg_match('/[^a-z\s]/i', $pattern)) {
            return $pattern;
        }

        $origPattern = $pattern;

        // Undo the \\x replacement, that is a fix for "Der gro\xdfe BilderSauger 2.00u" user agent match
        // @source https://github.com/browscap/browscap-php
        $pattern = preg_replace(
            ['/(?<!\\\\)\\.\\*/', '/(?<!\\\\)\\./', '/(?<!\\\\)\\\\x/'],
            ['\\*', '\\?', '\\x'],
            $pattern,
        );

        if ($pattern === null) {
            throw new UnexpectedValueException(
                sprintf('an error occured while handling pattern %s', $origPattern),
            );
        }

        // Undo preg_quote
        return str_replace(
            [
                '\\\\',
                '\\+',
                '\\*',
                '\\?',
                '\\[',
                '\\^',
                '\\]',
                '\\$',
                '\\(',
                '\\)',
                '\\{',
                '\\}',
                '\\=',
                '\\!',
                '\\<',
                '\\>',
                '\\|',
                '\\:',
                '\\-',
                '\\.',
                '\\/',
                '\\#',
            ],
            [
                '\\',
                '+',
                '*',
                '?',
                '[',
                '^',
                ']',
                '$',
                '(',
                ')',
                '{',
                '}',
                '=',
                '!',
                '<',
                '>',
                '|',
                ':',
                '-',
                '.',
                '/',
                '#',
            ],
            $pattern,
        );
    }
}
