<?php
namespace Crossjoin\Browscap\Updater;

/**
 * None updater class
 *
 * This updater does nothing, so if you set it, the source data won't be updated.
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
class None
extends AbstractUpdater
{
    /**
     * Gets the current browscap version (time stamp)
     *
     * @return int
     */
    public function getBrowscapVersion()
    {
        return 0;
    }

    /**
     * Gets the current browscap version number (if possible for the source)
     *
     * @return int|null
     */
    public function getBrowscapVersionNumber()
    {
        return null;
    }

    /**
     * Gets the browscap data of the used source type
     *
     * @return string|boolean
     */
    public function getBrowscapSource()
    {
        return false;
    }
}