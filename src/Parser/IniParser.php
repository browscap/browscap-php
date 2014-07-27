<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Refer to the LICENSE file distributed with this package.
 *
 * @category   Browscap
 * @package    Parser
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    MIT
 */

namespace phpbrowscap\Parser;

/**
 * Class IniParser
 *
 * @category   Browscap
 * @package    Parser
 * @author     James Titcumb <james@asgrim.com>
 */
class IniParser
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var bool
     */
    private $shouldSort = false;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $fileLines;

    /**
     * @param string $filename
     */
    public function __construct($filename = null)
    {
        $this->filename = $filename;
    }

    /**
     * @param bool $shouldSort
     *
     * @return \Browscap\Parser\IniParser
     */
    public function setShouldSort($shouldSort)
    {
        $this->shouldSort = (bool) $shouldSort;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldSort()
    {
        return $this->shouldSort;
    }

    /**
     * @return array
     */
    public function getParsed()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    private function getLinesFromFile()
    {
        $filename = $this->filename;

        if (!file_exists($filename)) {
            throw new \phpbrowscap\Exception\InvalidArgumentException("File not found: {$filename}");
        }

        return file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * @param string[] $fileLines
     */
    public function setFileLines(array $fileLines)
    {
        $this->fileLines = $fileLines;
    }

    /**
     * @return array
     */
    private function getFileLines()
    {
        if (!$this->fileLines) {
            $fileLines = $this->getLinesFromFile();
        } else {
            $fileLines = $this->fileLines;
        }

        return $fileLines;
    }

    /**
     * @return array
     * @throws \RuntimeException
     */
    public function parse()
    {
        $fileLines = $this->getFileLines();

        $data = array();

        $currentSection  = '';
        $currentDivision = '';

        for ($line = 0; $line < count($fileLines); $line++) {

            $currentLine       = ($fileLines[$line]);
            $currentLineLength = strlen($currentLine);

            if ($currentLineLength == 0) {
                continue;
            }

            if (substr($currentLine, 0, 40) == ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;') {
                $currentDivision = trim(substr($currentLine, 41));
                continue;
            }

            // We only skip comments that *start* with semicolon
            if ($currentLine[0] == ';') {
                continue;
            }

            if ($currentLine[0] == '[') {
                $currentSection = substr($currentLine, 1, ($currentLineLength - 2));
                continue;
            }

            $bits = explode("=", $currentLine);

            if (count($bits) > 2) {
                throw new \RuntimeException("Too many equals in line: {$currentLine}, in Division: {$currentDivision}");
            }

            if (count($bits) < 2) {
                $bits[1] = '';
            }

            $data[$currentSection][$bits[0]]   = trim($bits[1], '"');
            $data[$currentSection]['Division'] = $currentDivision;
        }

        if ($this->shouldSort()) {
            $data = $this->sortArrayAndChildArrays($data);
        }

        $this->data = $data;

        return $data;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function sortArrayAndChildArrays(array $array)
    {
        ksort($array);

        foreach ($array as $key => $childArray) {
            if (is_array($childArray) && !empty($childArray)) {
                $array[$key] = $this->sortArrayAndChildArrays($childArray);
            }
        }

        return $array;
    }
}