<?php
declare(strict_types = 1);

namespace BrowscapPHP\Helper;

/**
 * class to help getting the user agent
 */
final class Support implements SupportInterface
{
    /**
     * @var array
     */
    private $source = [];

    /**
     * The HTTP Headers that this application will look through to find the best
     * User Agent, if one is not specified
     *
     * @var array
     */
    private $userAgentHeaders = [
        'HTTP_X_DEVICE_USER_AGENT',
        'HTTP_X_ORIGINAL_USER_AGENT',
        'HTTP_X_OPERAMINI_PHONE_UA',
        'HTTP_X_SKYFIRE_PHONE',
        'HTTP_X_BOLT_PHONE_UA',
        'HTTP_USER_AGENT',
    ];

    /**
     * @param array|null $source
     */
    public function __construct(?array $source = null)
    {
        if (null === $source) {
            $source = [];
        }

        $this->source = $source;
    }

    /**
     * detect the useragent
     *
     * @return string
     */
    public function getUserAgent() : string
    {
        $userAgent = '';

        foreach ($this->userAgentHeaders as $header) {
            if (array_key_exists($header, $this->source)
                && $this->source[$header]
            ) {
                $userAgent = $this->cleanParam($this->source[$header]);

                break;
            }
        }

        return $userAgent;
    }

    /**
     * clean Parameters taken from GET or POST Variables
     *
     * @param string $param the value to be cleaned
     *
     * @return string
     */
    private function cleanParam(string $param) : string
    {
        return strip_tags(trim(urldecode($param)));
    }
}
