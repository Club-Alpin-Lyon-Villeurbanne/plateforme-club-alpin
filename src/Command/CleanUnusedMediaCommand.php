<?php

namespace App\Command;

use App\Repository\MediaUploadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:clean-unused-media',
    description: 'Clean unused media uploads older than a specified time',
)]
class CleanUnusedMediaCommand extends Command
{
    public function __construct(
        private MediaUploadRepository $mediaUploadRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = new \DateTimeImmutable('-1 day');
        $unusedMedia = $this->mediaUploadRepository->findUnusedOlderThan($date);

        $output->writeln(sprintf('Found %d unused media to delete', \count($unusedMedia)));

        foreach ($unusedMedia as $media) {
            $this->entityManager->remove($media);
            $output->writeln(sprintf('Deleting media #%d: %s', $media->getId(), $media->getFilename()));
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
