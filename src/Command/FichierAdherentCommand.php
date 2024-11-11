<?php

namespace App\Command;

use App\Service\FfcamSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fichier-adherent',
    description: 'Synchronise les adhérents depuis un fichier FFCAM'
)]
class FichierAdherentCommand extends Command
{
    public function __construct(private FfcamSynchronizer $synchronizer, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file-path', InputArgument::REQUIRED, 'Chemin du fichier FFCAM à traiter');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file-path');

        if (!file_exists($filePath)) {
            $io->error("Le fichier '$filePath' n'existe pas.");

            return Command::FAILURE;
        }

        $this->synchronizer->synchronize($filePath);

        return Command::SUCCESS;
    }
}
