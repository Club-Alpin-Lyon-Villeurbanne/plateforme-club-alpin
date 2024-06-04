<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'save-images')]
class SaveImagesCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require __DIR__ . '/../../legacy/app/cron/chron.save_images.php';

        return Command::SUCCESS;
    }
}
