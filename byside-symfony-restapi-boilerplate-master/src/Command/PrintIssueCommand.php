<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrintIssueCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'issue:print';

    protected function configure()
    {
        $this
            ->setDescription('Gets an Issue')
            ->setHelp('This command allows you to create a user...')
            ->addArgument('id', InputArgument::REQUIRED, 'Issue id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ... put here the code to create the user

        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;
    }
}
