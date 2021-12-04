<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RappelsCommand extends Command
{
    protected static $defaultName = 'rappels';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require __DIR__.'/../../legacy/app/cron/cron_rappel.php';

        return Command::SUCCESS;
    }
}
