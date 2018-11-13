<?php
declare(strict_types = 1);

namespace BrowscapPHP\Parser;

use BrowscapPHP\Formatter\FormatterInterface;
use BrowscapPHP\Parser\Helper\GetDataInterface;
use BrowscapPHP\Parser\Helper\GetPatternInterface;

/**
 * Ini parser class (compatible with PHP 5.3+)
 */
final class Ini implements ParserInterface
{
    /**
     * @var Helper\GetPatternInterface
     */
    private $patternHelper;

    /**
     * @var Helper\GetDataInterface
     */
    private $dataHelper;

    /**
     * Formatter to use
     *
     * @var \BrowscapPHP\Formatter\FormatterInterface
     */
    private $formatter;

    /**
     * class constructor
     *
     * @param \BrowscapPHP\Parser\Helper\GetPatternInterface $patternHelper
     * @param \BrowscapPHP\Parser\Helper\GetDataInterface    $dataHelper
     * @param \BrowscapPHP\Formatter\FormatterInterface      $formatter
     */
    public function __construct(
        GetPatternInterface $patternHelper,
        GetDataInterface $dataHelper,
        FormatterInterface $formatter
    ) {
        $this->patternHelper = $patternHelper;
        $this->dataHelper = $dataHelper;
        $this->formatter = $formatter;
    }

    /**
     * Gets the browser data formatr for the given user agent
     * (or null if no data avaailble, no even the default browser)
     *
     * @param  string                  $userAgent
     *
     * @throws \UnexpectedValueException
     *
     * @return FormatterInterface|null
     */
    public function getBrowser(string $userAgent) : ?FormatterInterface
    {
        $userAgent = strtolower($userAgent);
        $formatter = null;

        foreach ($this->patternHelper->getPatterns($userAgent) as $patterns) {
            $patternToMatch = '/^(?:' . str_replace("\t", ')|(?:', $patterns) . ')$/i';

            if (! preg_match($patternToMatch, $userAgent)) {
                continue;
            }

            // strtok() requires less memory than explode()
            $pattern = strtok($patterns, "\t");

            while (false !== $pattern) {
                $pattern = str_replace('[\d]', '(\d)', $pattern);
                $quotedPattern = '/^' . $pattern . '$/i';
                $matches = [];

                if (preg_match($quotedPattern, $userAgent, $matches)) {
                    // Insert the digits back into the pattern, so that we can search the settings for it
                    if (1 < count($matches)) {
                        array_shift($matches);
                        foreach ($matches as $oneMatch) {
                            $numPos = strpos($pattern, '(\d)');
                            $pattern = substr_replace($pattern, $oneMatch, $numPos, 4);
                        }
                    }

                    // Try to get settings - as digits have been replaced to speed up the pattern search (up to 90 faster),
                    // we won't always find the data in the first step - so check if settings have been found and if not,
                    // search for the next pattern.
                    $settings = $this->dataHelper->getSettings($pattern);

                    if (0 < count($settings)) {
                        $formatter = $this->formatter;
                        $formatter->setData($settings);

                        break 2;
                    }
                }

                $pattern = strtok("\t");
            }
        }

        return $formatter;
    }
}
