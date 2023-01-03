<?php

declare(strict_types=1);

namespace BrowscapPHP\IniParser;

use BrowscapPHP\Data\PropertyFormatter;
use BrowscapPHP\Data\PropertyHolder;
use BrowscapPHP\Helper\Quoter;
use BrowscapPHP\Parser\Helper\Pattern;
use BrowscapPHP\Parser\Helper\SubKey;
use Generator;
use JsonException;
use OutOfRangeException;
use UnexpectedValueException;

use function array_chunk;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function implode;
use function in_array;
use function is_array;
use function json_encode;
use function krsort;
use function ksort;
use function parse_ini_string;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpbrk;
use function strtolower;
use function usort;

use const INI_SCANNER_RAW;
use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_THROW_ON_ERROR;

/**
 * Ini parser class (compatible with PHP 5.3+)
 */
final class IniParser implements ParserInterface
{
    /**
     * Number of pattern to combine for a faster regular expression search.
     *
     * @important The number of patterns that can be processed in one step
     *            is limited by the internal regular expression limits.
     */
    private const COUNT_PATTERN = 50;

    /**
     * Creates new ini part cache files
     *
     * @throws OutOfRangeException
     * @throws UnexpectedValueException
     */
    public function createIniParts(string $content): Generator
    {
        // get all patterns from the ini file in the correct order,
        // so that we can calculate with index number of the resulting array,
        // which part to use when the ini file is splitted into its sections.
        preg_match_all('/(?<=\[)(?:[^\r\n]+)(?=\])/m', $content, $patternPositions);
        $patternPositions = $patternPositions[0];

        // split the ini file into sections and save the data in one line with a hash of the beloging
        // pattern (filtered in the previous step)
        $iniParts = preg_split('/\[[^\r\n]+\]/', $content);
        if ($iniParts === false) {
            throw new UnexpectedValueException('an error occured while splitting content into parts');
        }

        $contents = [];

        $propertyFormatter = new PropertyFormatter(new PropertyHolder());

        foreach ($patternPositions as $position => $pattern) {
            $pattern     = strtolower($pattern);
            $patternhash = Pattern::getHashForParts($pattern);
            $subkey      = SubKey::getIniPartCacheSubKey($patternhash);

            if (! isset($contents[$subkey])) {
                $contents[$subkey] = [];
            }

            if (! array_key_exists($position + 1, $iniParts)) {
                throw new OutOfRangeException(sprintf('could not find position %d inside iniparts', $position + 1));
            }

            $browserProperties = parse_ini_string($iniParts[$position + 1], false, INI_SCANNER_RAW);

            if ($browserProperties === false) {
                throw new UnexpectedValueException(sprintf('could ini parse position %d inside iniparts', $position + 1));
            }

            foreach (array_keys($browserProperties) as $property) {
                $browserProperties[$property] = $propertyFormatter->formatPropertyValue(
                    $browserProperties[$property],
                    $property,
                );
            }

            try {
                // the position has to be moved by one, because the header of the ini file
                // is also returned as a part
                $contents[$subkey][] = $patternhash . "\t" . json_encode(
                    $browserProperties,
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR,
                );
            } catch (JsonException $e) {
                throw new UnexpectedValueException('json encoding content failed', 0, $e);
            }
        }

        unset($patternPositions, $iniParts);

        $subkeys = array_flip(SubKey::getAllIniPartCacheSubKeys());
        foreach ($contents as $subkey => $cacheContent) {
            yield $subkey => $cacheContent;

            unset($subkeys[$subkey]);
        }

        foreach (array_keys($subkeys) as $subkey) {
            $subkey = (string) $subkey;

            yield $subkey => '';
        }
    }

    /**
     * Creates new pattern cache files
     *
     * @throws void
     */
    public function createPatterns(string $content): Generator
    {
        // get all relevant patterns from the INI file
        // - containing "*" or "?"
        // - not containing "*" or "?", but not having a comment
        preg_match_all(
            '/(?<=\[)(?:[^\r\n]*[?*][^\r\n]*)(?=\])|(?<=\[)(?:[^\r\n*?]+)(?=\])(?![^\[]*Comment=)/m',
            $content,
            $matches,
        );

        if (empty($matches[0]) || ! is_array($matches[0])) {
            yield '' => '';

            return;
        }

        $quoterHelper = new Quoter();
        $matches      = $matches[0];
        usort($matches, [$this, 'compareBcStrings']);

        // build an array to structure the data. this requires some memory, but we need this step to be able to
        // sort the data in the way we need it (see below).
        $data = [];

        foreach ($matches as $pattern) {
            if ($pattern === 'GJK_Browscap_Version') {
                continue;
            }

            $pattern     = strtolower($pattern);
            $patternhash = Pattern::getHashForPattern($pattern, false)[0];
            $tmpLength   = Pattern::getPatternLength($pattern);

            // special handling of default entry
            if ($tmpLength === 0) {
                $patternhash = str_repeat('z', 32);
            }

            if (! isset($data[$patternhash])) {
                $data[$patternhash] = [];
            }

            if (! isset($data[$patternhash][$tmpLength])) {
                $data[$patternhash][$tmpLength] = [];
            }

            $pattern = $quoterHelper->pregQuote($pattern);

            // Check if the pattern contains digits - in this case we replace them with a digit regular expression,
            // so that very similar patterns (e.g. only with different browser version numbers) can be compressed.
            // This helps to speed up the first (and most expensive) part of the pattern search a lot.
            if (strpbrk($pattern, '0123456789') !== false) {
                $compressedPattern = preg_replace('/\d/', '[\d]', $pattern);

                if (! in_array($compressedPattern, $data[$patternhash][$tmpLength])) {
                    $data[$patternhash][$tmpLength][] = $compressedPattern;
                }
            } else {
                $data[$patternhash][$tmpLength][] = $pattern;
            }
        }

        unset($matches);

        // sorting of the data is important to check the patterns later in the correct order, because
        // we need to check the most specific (=longest) patterns first, and the least specific
        // (".*" for "Default Browser")  last.
        //
        // sort by pattern start to group them
        ksort($data);
        // and then by pattern length (longest first)
        foreach (array_keys($data) as $key) {
            krsort($data[$key]);
        }

        // write optimized file (grouped by the first character of the has, generated from the pattern
        // start) with multiple patterns joined by tabs. this is to speed up loading of the data (small
        // array with pattern strings instead of an large array with single patterns) and also enables
        // us to search for multiple patterns in one preg_match call for a fast first search
        // (3-10 faster), followed by a detailed search for each single pattern.
        $contents = [];
        foreach ($data as $patternhash => $tmpEntries) {
            if (empty($tmpEntries)) {
                continue;
            }

            $subkey = SubKey::getPatternCacheSubkey($patternhash);

            if (! isset($contents[$subkey])) {
                $contents[$subkey] = [];
            }

            foreach ($tmpEntries as $tmpLength => $tmpPatterns) {
                if (empty($tmpPatterns)) {
                    continue;
                }

                $chunks = array_chunk($tmpPatterns, self::COUNT_PATTERN);

                foreach ($chunks as $chunk) {
                    $contents[$subkey][] = $patternhash . "\t" . $tmpLength . "\t" . implode("\t", $chunk);
                }
            }
        }

        unset($data);

        $subkeys = SubKey::getAllPatternCacheSubkeys();
        foreach ($contents as $subkey => $content) {
            yield $subkey => $content;

            unset($subkeys[$subkey]);
        }

        foreach (array_keys($subkeys) as $subkey) {
            $subkey = (string) $subkey;

            yield $subkey => '';
        }
    }

    /** @throws void */
    private function compareBcStrings(string $a, string $b): int
    {
        $aLength = strlen($a);
        $bLength = strlen($b);

        if ($aLength > $bLength) {
            return -1;
        }

        if ($aLength < $bLength) {
            return 1;
        }

        $aLength = strlen(str_replace(['*', '?'], '', $a));
        $bLength = strlen(str_replace(['*', '?'], '', $b));

        if ($aLength > $bLength) {
            return -1;
        }

        if ($aLength < $bLength) {
            return 1;
        }

        return 0;
    }
}
