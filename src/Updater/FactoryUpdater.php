<?php
namespace Crossjoin\Browscap\Updater;

/**
 * Updater factory class
 *
 * This class checks the current settings and returns the best matching
 * updater instance (except the local updater, which requires additional
 * settings and can therefore only be set manually).
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
class FactoryUpdater
{
    /**
     * Get a available updater instance, or returns NULL is none available.
     *
     * @return \Crossjoin\Browscap\Updater\AbstractUpdater|null
     */
    public static function getInstance()
    {
        if (function_exists('curl_init')) {
            return new Curl();
        } elseif ((bool)(int)ini_get('allow_url_fopen') !== false) {
            return new FileGetContents();
        }
        
        return new None();
    }
}
