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
 * @package    Command
 * @copyright  1998-2014 Browser Capabilities Project
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 * @since      added with version 3.0
 */

namespace phpbrowscap\Command;

use phpbrowscap\Browscap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use phpbrowscap\Helper\LoggerHelper;
use phpbrowscap\Helper\IniLoader;
use WurflCache\Adapter\File;
use phpbrowscap\Cache\BrowscapCache;

/**
 * command to fetch a browscap ini file from the remote host, convert it into an array and store the content in a local
 * file
 *
 * @category   Browscap-PHP
 * @package    Command
 * @author     Dave Olsen, http://dmolsen.com
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 1998-2014 Browser Capabilities Project
 * @version    3.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/browscap/browscap-php/
 */
class UpdateCommand extends Command
{
    /**
     * @var \phpbrowscap\Cache\BrowscapCache
     */
    private $cache = null;

    /**
     * @param \phpbrowscap\Cache\BrowscapCache $cache
     */
    public function __construct(BrowscapCache $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('browscap:update')
            ->setDescription('Fetches an updated INI file for Browscap and overwrites the current PHP file.')
            ->addOption(
                'no-backup',
                null,
                InputOption::VALUE_NONE,
                'Do not backup the previously existing file'
            )
            ->addOption(
                'debug',
                'd',
                InputOption::VALUE_NONE,
                'Should the debug mode entered?'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loggerHelper = new LoggerHelper();
        $logger       = $loggerHelper->create($input->getOption('debug'));

        $logger->info('started updating cache with remote file');

        $browscap = new Browscap();

        $browscap
            ->setLogger($logger)
            ->setCache($this->cache)
            ->update(IniLoader::PHP_INI)
        ;

        $logger->info('finished updating cache with remote file');
    }
}
