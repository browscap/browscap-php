<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace phpbrowscap\Exception;

class FetcherException extends DomainException
{
    public static function httpError($resource, $error)
    {
        return new static(
            sprintf('Could not fetch HTTP resource "%s": %s', $resource, $error)
        );
    }
}