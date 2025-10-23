<?php

namespace App\Command;

use App\Entity\UserNiveau;
use App\Repository\UserNiveauRepository;
use App\Repository\UserRepository;
use App\Service\UserLicenseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'user-deletion-cron',
    description: 'Cron de suppression ou anonymisation des adhérents trop vieux'
)]
class UserDeletionCronCommand extends Command
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserNiveauRepository $userNiveauRepository,
        protected UserLicenseHelper $userLicenseHelper,
        protected LoggerInterface $logger,
        protected readonly EntityManagerInterface $manager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('User deletion: find users to delete or anonymize');

        // date max d'adhésion qu'on conserve : 31/08 de la saison N-2
        // ex : pour la saison 2026-2027 (1ère occurrence de ce traitement), on conserve jusqu'au 31/08/2024 inclus
        $endDateTime = $this->userLicenseHelper->getLicenseExpirationDate(2);

        $usersToDelete = $this->userRepository->findUsersToDelete($endDateTime);
        $this->logger->info('User deletion: ' . count($usersToDelete) . ' users to delete');

        $deleted = 0;
        foreach ($usersToDelete as $user) {
            $level = $this->userNiveauRepository->findOneBy(['idUser' => $user]);
            if ($level instanceof UserNiveau) {
                $this->manager->remove($level);
            }
            $this->manager->remove($user);
            ++$deleted;
        }
        $this->manager->flush();
        $this->logger->info('User deletion: ' . $deleted . ' users deleted');

        $this->logger->info('User deletion: no (more) users to delete or anonymize');

        return Command::SUCCESS;
    }
}
