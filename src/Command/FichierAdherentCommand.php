<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FichierAdherentCommand extends Command
{
    protected static $defaultName = 'fichier-adherent';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require __DIR__.'/../../legacy/app/cron/cron_fichier_adherent.php';

        return Command::SUCCESS;
    }
}
