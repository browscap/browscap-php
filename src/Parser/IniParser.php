<?php
/**
 * Copyright (c) 1998-2014 Browser Capabilities Project
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   Browscap-PHP
 * @package    Parser
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace BrowscapPHP\Parser;

/**
 * parses the ini data into an array of sections with their data
 *
 * @category   Browscap-PHP
 * @package    Parser
 * @author     James Titcumb <james@asgrim.com>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class IniParser
{
    /**
     * @var string
     */
    private $filename = null;

    /**
     * @var bool
     */
    private $shouldSort = false;

    /**
     * @var array
     */
    private $data = array();

    /**
     * @var array
     */
    private $fileLines = array();

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
     * @return IniParser
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
            throw new \BrowscapPHP\Exception\InvalidArgumentException("File not found: {$filename}");
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
