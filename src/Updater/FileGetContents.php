<?php
namespace Crossjoin\Browscap\Updater;

/**
 * FileGetContents updater class
 *
 * This class loads the source data using the file_get_contents() function.
 * Please note, that this requires 'allow_url_fopen' set to '1' to work
 * with remote files.
 *
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Christoph Ziegenberg <christoph@ziegenberg.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @copyright Copyright (c) 2014 Christoph Ziegenberg <christoph@ziegenberg.com>
 * @version 0.1
 * @license http://www.opensource.org/licenses/MIT MIT License
 * @link https://github.com/crossjoin/browscap
 */
class FileGetContents
extends AbstractUpdaterRemote
{
    /**
     * Name of the update method, used in the user agent for the request,
     * for browscap download statistics. Has to be overwritten by the
     * extending class.
     *
     * @var string
     */
    protected $updateMethod = 'URL-wrapper';

    public function __construct($options = null)
    {
        parent::__construct($options);

        if ((bool)(int)ini_get('allow_url_fopen') === false) {
            throw new \Exception("Please activate 'allow_url_fopen'.");
        }
    }

    /**
     * Gets the data from a given URL (or false on failure)
     *
     * @param string $url
     * @return string|boolean
     * @throws \RuntimeException
     */
    protected function getRemoteData($url)
    {
        $context = $this->getStreamContext();
        $return  = file_get_contents($url, false, $context);

        // $http_response_header is a predefined variables,
        // automatically created by PHP after the call above
        //
        // @see http://php.net/manual/en/reserved.variables.httpresponseheader.php
        if (isset($http_response_header)) {
            // extract status from first array entry, e.g. from 'HTTP/1.1 200 OK'
            if (is_array($http_response_header) && isset($http_response_header[0])) {
                $tmp_status_parts = explode(" ", $http_response_header[0], 3);
                $http_code = $tmp_status_parts[1];

                // check for HTTP error
                $http_exception = $this->getHttpErrorException($http_code);
                if ($http_exception !== null) {
                    throw $http_exception;
                }
            }
        }

        return $return;
    }

    protected function getStreamContext()
    {
        // set basic stream context configuration
        $config = array(
            'http' => array(
                'user_agent'    => $this->getUserAgent(),
                // ignore errors, handle them manually
                'ignore_errors' => true,
            )
        );

        // check and set proxy settings
        $proxy_host = $this->getOption('ProxyHost');
        if ($proxy_host !== null) {
            // check for supported protocol
            $proxy_protocol = $this->getOption('ProxyProtocol');
            if ($proxy_protocol !== null) {
                if (!in_array($proxy_protocol, array(self::PROXY_PROTOCOL_HTTP, self::PROXY_PROTOCOL_HTTPS))) {
                    throw new \RuntimeException("Invalid/unsupported value '$proxy_protocol' for option 'ProxyProtocol'.");
                }
            } else {
                $proxy_protocol = self::PROXY_PROTOCOL_HTTP;
            }

            // prepare port for the proxy server address
            $proxy_port = $this->getOption('ProxyPort');
            if ($proxy_port !== null) {
                $proxy_port = ":" . $proxy_port;
            } else {
                $proxy_port = "";
            }

            // check auth settings
            $proxy_auth = $this->getOption('ProxyAuth');
            if ($proxy_auth !== null) {
                if (!in_array($proxy_auth, array(self::PROXY_AUTH_BASIC))) {
                    throw new \RuntimeException("Invalid/unsupported value '$proxy_auth' for option 'ProxyAuth'.");
                }
            } else {
                $proxy_auth = self::PROXY_AUTH_BASIC;
            }

            // set proxy server address
            $config['http']['proxy'] = 'tcp://' . $proxy_host . $proxy_port;
            // full uri required by some proxy servers
            $config['http']['request_fulluri'] = true;

            // add authorization header if required
            $proxy_user = $this->getOption('ProxyUser');
            if ($proxy_user !== null) {
                $proxy_password = $this->getOption('ProxyPassword');
                if ($proxy_password === null) {
                    $proxy_password = '';
                }
                $auth = base64_encode($proxy_user . ":" . $proxy_password);
                $config['http']['header'] = "Proxy-Authorization: Basic " . $auth;
            }

            if ($proxy_protocol === self::PROXY_PROTOCOL_HTTPS) {
                // @todo Add SSL context options
                // @see  http://www.php.net/manual/en/context.ssl.php
            }
        }
        return stream_context_create($config);
    }
}