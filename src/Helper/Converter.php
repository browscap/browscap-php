<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace phpbrowscap\Helper;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use UAParser\Exception\FileNotFoundException;

class Converter
{
    /** @var string */
    const BROWSCAP_VERSION_KEY = 'GJK_Browscap_Version';

    /**
     * Current cache version
     */
    const CACHE_FILE_VERSION = '2.0b';

    /**
     * Options for regex patterns.
     *
     * REGEX_DELIMITER: Delimiter of all the regex patterns in the whole class.
     * REGEX_MODIFIERS: Regex modifiers.
     */
    const REGEX_DELIMITER = '@';
    const REGEX_MODIFIERS = 'i';
    const COMPRESSION_PATTERN_START = '@';
    const COMPRESSION_PATTERN_DELIMITER = '|';

    /** @var string */
    private $destination = null;

    /** @var \Symfony\Component\Filesystem\Filesystem */
    private $fs = null;

    /** @var \phpbrowscap\Parser\IniParser */
    private $parser = null;

    /**
     * @var int
     */
    private $_source_version = 0;

    /**
     * @param string $destination
     * @param \Symfony\Component\Filesystem\Filesystem $fs
     */
    public function __construct($destination, Filesystem $fs = null)
    {
        $this->destination = $destination;
        $this->fs = $fs ? $fs : new Filesystem();
        $this->parser = new \phpbrowscap\Parser\IniParser();
        $this->parser->setShouldSort(false);
    }

    /**
     * @param string $iniFile
     * @param bool $backupBeforeOverride
     * @throws FileNotFoundException
     */
    public function convertFile($iniFile, $backupBeforeOverride = true)
    {
        if (!$this->fs->exists($iniFile)) {
            throw FileNotFoundException::fileNotFound($iniFile);
        }

        $this->convertString(file_get_contents($iniFile), $backupBeforeOverride);
    }

    /**
     * @param string $iniString
     * @param bool $backupBeforeOverride
     */
    public function convertString($iniString, $backupBeforeOverride = true)
    {
        if (false !== strpos("\r\n", $iniString)) {
            $fileLines = explode("\r\n", $iniString);
        } else {
            $fileLines = explode("\n", $iniString);
        }

        $this->parser->setFileLines($fileLines);

        $this->doConvert($this->parser->parse(), $backupBeforeOverride);
    }

    /**
     * @param array $browsers
     * @param bool  $backupBeforeOverride
     */
    private function doConvert(array $browsers, $backupBeforeOverride = true)
    {
        $data = $this->buildCache($browsers);

        $regexesFile = $this->destination . '/cache.php';
        if ($backupBeforeOverride && $this->fs->exists($regexesFile)) {

            $currentHash = hash('sha512', file_get_contents($regexesFile));
            $futureHash = hash('sha512', $data);

            if ($futureHash === $currentHash) {
                return;
            }

            $backupFile = $this->destination . '/cache-' . $currentHash . '.php';
            $this->fs->copy($regexesFile, $backupFile);
        }

        $this->fs->dumpFile($regexesFile, $data);
    }

    /**
     * XXX save
     *
     * Parses the ini file and updates the cache files
     *
     * @param array $browsers
     *
     * @return bool whether the file was correctly written to the disk
     */
    private function buildCache(array $browsers)
    {
        $this->_source_version = $browsers[self::BROWSCAP_VERSION_KEY]['Version'];
        unset($browsers[self::BROWSCAP_VERSION_KEY]);

        unset($browsers['DefaultProperties']['RenderingEngine_Description']);

        $_properties = array_keys($browsers['DefaultProperties']);

        array_unshift(
            $_properties,
            'browser_name',
            'browser_name_regex',
            'browser_name_pattern',
            'Parent'
        );

        $tmp_user_agents  = array_keys($browsers);
        $user_agents_keys = array_flip($tmp_user_agents);
        $properties_keys  = array_flip($_properties);

        $tmp_patterns = array();
        $_userAgents  = array();
        $_browsers    = array();

        foreach ($tmp_user_agents as $i => $user_agent) {
            if (empty($browsers[$user_agent]['Comment'])
                || false !== strpos($user_agent, '*')
                || false !== strpos($user_agent, '?')
            ) {
                $quoterHelper = new \phpbrowscap\Helper\Quoter();
                $pattern = $quoterHelper->pregQuote($user_agent);

                $matches_count = preg_match_all('@\d+@', $pattern, $matches);

                if (!$matches_count) {
                    $tmp_patterns[$pattern] = $i;
                } else {
                    $compressed_pattern = preg_replace('@\d+@', '(\d+)', $pattern);

                    if (!isset($tmp_patterns[$compressed_pattern])) {
                        $tmp_patterns[$compressed_pattern] = array('first' => $pattern);
                    }

                    $tmp_patterns[$compressed_pattern][$i] = $matches[0];
                }
            }

            if (!empty($browsers[$user_agent]['Parent'])) {
                $parent = $browsers[$user_agent]['Parent'];

                $parent_key = $user_agents_keys[$parent];

                $browsers[$user_agent]['Parent']       = $parent_key;
                $_userAgents[$parent_key . '.0'] = $tmp_user_agents[$parent_key];
            };

            $browser = array();
            foreach ($browsers[$user_agent] as $key => $value) {
                if (!isset($properties_keys[$key])) {
                    continue;
                }

                $key           = $properties_keys[$key];
                $browser[$key] = $value;
            }

            $_browsers[] = $browser;
        }

        // reducing memory usage by unsetting $tmp_user_agents
        unset($tmp_user_agents);

        $_patterns = array();

        foreach ($tmp_patterns as $pattern => $pattern_data) {
            if (is_int($pattern_data)) {
                $_patterns[$pattern] = $pattern_data;
            } elseif (2 == count($pattern_data)) {
                end($pattern_data);
                $_patterns[$pattern_data['first']] = key($pattern_data);
            } else {
                unset($pattern_data['first']);

                $pattern_data = $this->deduplicateCompressionPattern($pattern_data, $pattern);

                $_patterns[$pattern] = $pattern_data;
            }
        }

        // Get the whole PHP code
        $cacheTpl = "<?php\n\$source_version=%s;\n\$cache_version=%s;\n\$properties=%s;\n\$browsers=%s;\n\$userAgents=%s;\n\$patterns=%s;\n";

        $propertiesArray = $this->_array2string($_properties);
        $patternsArray   = $this->_array2string($_patterns);
        $userAgentsArray = $this->_array2string($_userAgents);
        $browsersArray   = $this->_array2string($_browsers);

        return sprintf(
            $cacheTpl,
            "'" . $this->_source_version . "'",
            "'" . self::CACHE_FILE_VERSION . "'",
            $propertiesArray,
            $browsersArray,
            $userAgentsArray,
            $patternsArray
        );
    }

    /**
     * Converts the given array to the PHP string which represent it.
     * This method optimizes the PHP code and the output differs form the
     * var_export one as the internal PHP function does not strip whitespace or
     * convert strings to numbers.
     *
     * @param array $array the array to parse and convert
     *
     * @return string the array parsed into a PHP string
     */
    private function _array2string($array)
    {
        $strings = array();

        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $key = '';
            } elseif (ctype_digit((string) $key) || '.0' === substr($key, -2)) {
                $key = intval($key) . '=>';
            } else {
                $key = "'" . str_replace("'", "\'", $key) . "'=>";
            }

            if (is_array($value)) {
                $value = "'" . addcslashes(serialize($value), "'") . "'";
            } elseif (ctype_digit((string) $value)) {
                $value = intval($value);
            } else {
                $value = "'" . str_replace("'", "\'", $value) . "'";
            }

            $strings[] = $key . $value;
        }

        return "array(\n" . implode(",\n", $strings) . "\n)";
    }

    /**
     * That looks complicated...
     *
     * All numbers are taken out into $matches, so we check if any of those numbers are identical
     * in all the $matches and if they are we restore them to the $pattern, removing from the $matches.
     * This gives us patterns with "(\d+)" only in places that differ for some matches.
     *
     * @param array  $matches
     * @param string $pattern
     *
     * @return array of $matches
     */
    private function deduplicateCompressionPattern($matches, &$pattern)
    {
        $tmp_matches = $matches;
        $first_match = array_shift($tmp_matches);
        $differences = array();

        foreach ($tmp_matches as $some_match) {
            $differences += array_diff_assoc($first_match, $some_match);
        }

        $identical = array_diff_key($first_match, $differences);

        $prepared_matches = array();

        foreach ($matches as $i => $some_match) {
            $key = self::COMPRESSION_PATTERN_START
                . implode(self::COMPRESSION_PATTERN_DELIMITER, array_diff_assoc($some_match, $identical));

            $prepared_matches[$key] = $i;
        }

        $pattern_parts = explode('(\d+)', $pattern);

        foreach ($identical as $position => $value) {
            $pattern_parts[$position + 1] = $pattern_parts[$position] . $value . $pattern_parts[$position + 1];
            unset($pattern_parts[$position]);
        }

        $pattern = implode('(\d+)', $pattern_parts);

        return $prepared_matches;
    }
}
