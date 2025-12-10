<?php

namespace App\Command;

use App\Service\FfcamSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ffcam-discovery-file-sync',
    description: 'Synchronise les adhérents depuis un fichier FFCAM découverte'
)]
class FfcamDiscoveryFileSync extends Command
{
    public function __construct(
        private readonly FfcamSynchronizer $synchronizer,
        private readonly string $ffcamFilePath,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!file_exists($this->ffcamFilePath)) {
            $io->error("Le fichier '$this->ffcamFilePath' n'existe pas.");

            return Command::FAILURE;
        }

        $this->synchronizer->discoverySynchronize($this->ffcamFilePath);

        return Command::SUCCESS;
    }
}
