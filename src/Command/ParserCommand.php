<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace phpbrowscap\Command;

use phpbrowscap\Browscap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * commands to parse a given useragent
 *
 * @author Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 */
class ParserCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('browscap:parse')
            ->setDescription('Parses a user agent string and dumps the results.')
            ->addArgument(
                'user-agent',
                null,
                InputArgument::REQUIRED,
                'User agent string to analyze'
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
        $browscap = new Browscap();
        $result   = $browscap->getBrowser($input->getArgument('user-agent'));

        $output->writeln(json_encode($result, JSON_PRETTY_PRINT));
    }
}
