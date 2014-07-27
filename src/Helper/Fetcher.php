<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace phpbrowscap\Helper;

use FileLoader\Loader;
use phpbrowscap\Exception\FetcherException;

class Fetcher
{
    private $resourceUri = 'https://raw.github.com/tobie/ua-parser/master/regexes.yaml';

    /** @var resource */
    private $streamContext;

    public function fetch()
    {
        //$level = error_reporting(0);

        $loader = new Loader();
        $loader
            ->setRemoteDataUrl('http://browscap.org/stream?q=PHP_BrowscapINI')
            ->setRemoteVerUrl('http://browscap.org/version')
            ->setMode(null)
            ->setTimeout(5)
        ;

        $result = $loader->load();

        //error_reporting($level);

        if ($result === false) {
            $error = error_get_last();
            throw FetcherException::httpError($this->resourceUri, $error['message']);
        }

        return $result;
    }
}
