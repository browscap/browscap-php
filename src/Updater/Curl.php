<?php
namespace Crossjoin\Browscap\Updater;

/**
 * Curl updater class
 *
 * This class loads the source data using the curl extension.
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
class Curl
extends AbstractUpdaterRemote
{
    /**
     * Name of the update method, used in the user agent for the request,
     * for browscap download statistics. Has to be overwritten by the
     * extending class.
     *
     * @var string
     */
    protected $updateMethod = 'cURL';

    public function __construct($options = null)
    {
        parent::__construct($options);

        // add additional options
        $this->options['ConnectTimeout'] = 5;
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
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getOption('ConnectTimeout'));
        curl_setopt($ch, CURLOPT_USERAGENT, $this->getUserAgent());

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

            $proxy_port = $this->getOption('ProxyPort');

            // check auth settings
            $proxy_auth = $this->getOption('ProxyAuth');
            if ($proxy_auth !== null) {
                if (!in_array($proxy_auth, array(self::PROXY_AUTH_BASIC, self::PROXY_AUTH_NTLM))) {
                    throw new \RuntimeException("Invalid/unsupported value '$proxy_auth' for option 'ProxyAuth'.");
                }
            } else {
                $proxy_auth = self::PROXY_AUTH_BASIC;
            }
            $proxy_user     = $this->getOption('ProxyUser');
            $proxy_password = $this->getOption('ProxyPassword');

            // set basic proxy options
            curl_setopt($ch, CURLOPT_PROXY, $proxy_protocol . "://" . $proxy_host);
            if ($proxy_port !== null) {
                curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
            }

            // set proxy auth options
            if ($proxy_user !== null) {
                if ($proxy_auth === self::PROXY_AUTH_NTLM) {
                    curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
                }
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_user . ":" . $proxy_password);
            }
        }

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // check for HTTP error
        $http_exception = $this->getHttpErrorException($http_code);
        if ($http_exception !== null) {
            throw $http_exception;
        }

        return $response;
    }
}