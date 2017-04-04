<?php
declare(strict_types=1);

namespace BrowscapPHP\Util\Logfile;

/**
 * reader to analyze the common log file of apache
 */
final class ApacheCommonLogFormatReader extends AbstractReader
{
    /**
     * @return string
     */
    protected function getRegex() : string
    {
        return '/^'
            . '(\S+)'                            # remote host (IP)
            . '\s+'
            . '(\S+)'                            # remote logname
            . '\s+'
            . '(\S+)'                            # remote user
            . '.*'
            . '\[([^]]+)\]'                      # date/time
            . '[^"]+'
            . '\"(.*)\"'                         # Verb(GET|POST|HEAD) Path HTTP Version
            . '\s+'
            . '(.*)'                             # Status
            . '\s+'
            . '(.*)'                             # Length (include Header)
            . '[^"]+'
            . '\"(.*)\"'                         # Referrer
            . '[^"]+'
            . '\"(?P<userAgentString>.+?)\".*'   # User Agent
            . '$/x';
    }
}
