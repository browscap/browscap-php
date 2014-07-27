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
use phpbrowscap\Exception\FileNotFoundException;
use phpbrowscap\Cache\BrowscapCache;

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
    
    /** @var \Monolog\Logger */
    private $logger = null;

    /**
     * The cache instance
     *
     * @var \phpbrowscap\Cache\BrowscapCache
     */
    private $cache = null;

    /**
     * Number of pattern to combine for a faster regular expression search.
     *
     * @important The number of patterns that can be processed in one step
     *            is limited by the internal regular expression limits.
     * @var int
     */
    private $joinPatterns = 100;

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
    
    public function setLogger($logger)
    {
        $this->logger = $logger;
        
        return $this;
    }

    /**
     * Sets a cache instance
     *
     * @param \phpbrowscap\Cache\BrowscapCache $cache
     *
     * @return \phpbrowscap\Browscap
     */
    public function setCache(BrowscapCache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param string $iniFile
     * @param bool $backupBeforeOverride
     * @throws \phpbrowscap\Exception\FileNotFoundException
     */
    public function convertFile($iniFile, $backupBeforeOverride = true)
    {
        if (!$this->fs->exists($iniFile)) {
            throw FileNotFoundException::fileNotFound($iniFile);
        }
        
        $this->logger->info('start reading file');
        
        $iniString = file_get_contents($iniFile);
        
        $this->logger->info('finished reading file');

        $this->convertString($iniString, $backupBeforeOverride);
    }

    /**
     * @param string $iniString
     * @param bool $backupBeforeOverride
     */
    public function convertString($iniString, $backupBeforeOverride = true)
    {
        $this->logger->info('start changing file into an array');
        
        //if (false !== strpos("\r\n", $iniString)) {
        //    $fileLines = explode("\r\n", $iniString);
        //} else {
        //    $fileLines = explode("\n", $iniString);
        //}
        
        //$this->parser->setFileLines($fileLines);
        //$browsers = $this->parser->parse();
        
        $this->createPatterns($iniString);
        $this->createIniParts($iniString);
        /*
        $browsers = parse_ini_string($iniString, true, INI_SCANNER_RAW);
        
        $this->logger->info('finished changing file into an array');
        $this->logger->info('started building the cache');
        
        $_source_version = $browsers[self::BROWSCAP_VERSION_KEY]['Version'];
        
        $this->cache->setItem('browscap.version', $_source_version, false);
        
        unset($browsers[self::BROWSCAP_VERSION_KEY]);
        unset($browsers['DefaultProperties']['RenderingEngine_Description']);

        $_properties     = array_keys($browsers['DefaultProperties']);
        $tmp_user_agents = array_keys($browsers);

        array_unshift(
            $_properties,
            'browser_name',
            'browser_name_regex',
            'browser_name_pattern',
            'Parent'
        );

        $this->cache->setItem('browscap.properties', $_properties);
        //$this->cache->setItem('browscap.useragents', $tmp_user_agents);
        
        $this->logger->info('finished storing properties and useragents');
        $this->logger->info('started saving browsers');
        
        //$user_agents_keys = array_flip($tmp_user_agents);
        //$properties_keys  = array_flip($_properties);

        $tmp_patterns = array();
        $_patterns    = array();
        $quoterHelper = new \phpbrowscap\Helper\Quoter();

        foreach ($tmp_user_agents as $i => $user_agent) {
            if (!empty($browsers[$user_agent]['Comment'])
                && false === strpos($user_agent, '*')
                && false === strpos($user_agent, '?')
            ) {
                continue;
            }
            
            $this->logger->debug('processing: ' . $user_agent);
            
            $pattern       = $quoterHelper->pregQuote($user_agent);
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
            
            $browser = $value = $browsers[$user_agent];
            
            while (array_key_exists('Parent', $value)) {
                $value    = $browsers[$value['Parent']];
                $browser += $value;
            }

            $this->cache->setItem('browscap.browsers.' . $i, $browser);
        }
        
        $this->logger->info('finished saving browsers');

        // reducing memory usage by unsetting $tmp_user_agents
        unset($tmp_user_agents);
        
        $this->logger->info('started deduplicating patterns');
        
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
        
        $this->cache->setItem('browscap.pattern', $_patterns);
        
        $this->logger->info('finished deduplicating patterns');
        /**/
    }

    /**
     * XXX save
     *
     * Parses the ini file and updates the cache files
     *
     * @return bool whether the file was correctly written to the disk
     */
    private function buildCache(array $browsers)
    {
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
    private function array2string($array)
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

    /**
     * Creates new ini part cache files
     */
    private function createIniParts($content)
    {
        // get all patterns from the ini file in the correct order,
        // so that we can calculate with index number of the resulting array,
        // which part to use when the ini file is splitted into its sections.
        preg_match_all('/(?<=\[)(?:[^\r\n]+)(?=\])/m', $content, $patternpositions);
        $patternpositions = $patternpositions[0];

        // split the ini file into sections and save the data in one line with a hash of the beloging
        // pattern (filtered in the previous step)
        $ini_parts = preg_split('/\[[^\r\n]+\]/', $content);
        $contents  = array();
        foreach ($patternpositions as $position => $pattern) {
            $patternhash = md5($pattern);
            $subkey      = $this->getIniPartCacheSubkey($patternhash);
            if (!isset($contents[$subkey])) {
                $contents[$subkey] = '';
            }

            // the position has to be moved by one, because the header of the ini file
            // is also returned as a part
            $contents[$subkey] .= $patternhash . json_encode(
                parse_ini_string($ini_parts[($position + 1)]),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ) . "\n";
        }
        
        unset($patternpositions);
        
        foreach ($contents as $subkey => $content) {
            $this->cache->setItem('browscap.iniparts.' . $subkey, $content);
        }
    }

    /**
     * Gets the subkey for the ini parts cache file, generated from the given string
     *
     * @param string $string
     * @return string
     */
    private function getIniPartCacheSubkey($string) 
    {
        return $string[0] . $string[1];
    }

    /**
     * Creates new pattern cache files
     */
    private function createPatterns($content)
    {
        // get all relevant patterns from the INI file
        // - containing "*" or "?"
        // - not containing "*" or "?", but not having a comment
        preg_match_all('/(?<=\[)(?:[^\r\n]*[?*][^\r\n]*)(?=\])|(?<=\[)(?:[^\r\n*?]+)(?=\])(?![^\[]*Comment=)/m', $content, $matches);
        $matches = $matches[0];

        if (!count($matches)) {
            return false;
        }
        
        // build an array to structure the data. this requires some memory, but we need this step to be able to
        // sort the data in the way we need it (see below).
        $data = array();
        foreach ($matches as $match) {
            // get the first characters for a fast search
            $tmp_start  = $this->getPatternStart($match);
            $tmp_length = $this->getPatternLength($match);

            // special handling of default entry
            if ($tmp_length === 0) {
                $tmp_start = str_repeat('z', 32);
            }

            if (!isset($data[$tmp_start])) {
                $data[$tmp_start] = array();
            }
            if (!isset($data[$tmp_start][$tmp_length])) {
                $data[$tmp_start][$tmp_length] = array();
            }
            $data[$tmp_start][$tmp_length][] = $match;
        }
        
        unset($matches);

        // write optimized file (grouped by the first character of the has, generated from the pattern
        // start) with multiple patterns joined by tabs. this is to speed up loading of the data (small
        // array with pattern strings instead of an large array with single patterns) and also enables
        // us to search for multiple patterns in one preg_match call for a fast first search
        // (3-10 faster), followed by a detailed search for each single pattern.
        $contents = array();
        foreach ($data as $tmp_start => $tmp_entries) {
            foreach ($tmp_entries as $tmp_length => $tmp_patterns) {
                for ($i = 0, $j = ceil(count($tmp_patterns) / $this->joinPatterns); $i < $j; $i++) {
                    $tmp_joinpatterns = implode("\t", array_slice($tmp_patterns, ($i * $this->joinPatterns), $this->joinPatterns));
                    $tmp_subkey       = $this->getIniPartCacheSubkey($tmp_start);
                    
                    if (!isset($contents[$tmp_subkey])) {
                        $contents[$tmp_subkey] = '';
                    }
                    
                    $contents[$tmp_subkey] .= $tmp_start . " " . $tmp_length . " " . $tmp_joinpatterns . "\n";
                }
            }
        }
        
        unset($data);
        
        foreach ($contents as $subkey => $content) {
            $this->cache->setItem('browscap.patterns.' . $subkey, $content, true);
        }
        
        return true;
    }

    /**
     * Gets a hash from the first charcters of a pattern/user agent, that can be used for a fast comparison,
     * by comparing only the hashes, without having to match the complete pattern against the user agent.
     *
     * @param string $pattern
     * @return string
     */
    private function getPatternStart($pattern)
    {
        return md5(preg_replace('/^([^\*\?\s]*)[\*\?\s].*$/', '\\1', substr($pattern, 0, 32)));
    }

    /**
     * Gets the minimum length of the patern (used in the getPatterns() method to
     * check against the user agent length)
     *
     * @param string $pattern
     * @return int
     */
    private function getPatternLength($pattern)
    {
        return strlen(str_replace('*', '', $pattern));
    }
}
