<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\BrevetAdherentRepository;
use App\Repository\UserAttrRepository;
use App\Repository\UserNiveauRepository;
use App\Repository\UserNotificationRepository;
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
    description: 'Cron de suppression et anonymisation des adhérents ayant une licence trop ancienne'
)]
class UserDeletionCronCommand extends Command
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserNiveauRepository $userNiveauRepository,
        protected BrevetAdherentRepository $brevetAdherentRepository,
        protected UserNotificationRepository $userNotificationRepository,
        protected UserAttrRepository $userAttrRepository,
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

        // adhérents sans activité => on supprime complètement
        $usersToDelete = $this->userRepository->findUsersToDelete($endDateTime);
        $this->logger->info('User deletion: ' . count($usersToDelete) . ' users to delete');

        $deleted = 0;
        /** @var User $user */
        foreach ($usersToDelete as $user) {
            $this->userNiveauRepository->deleteByUser($user);
            $this->brevetAdherentRepository->deleteByUser($user);

            $this->manager->remove($user);
            ++$deleted;
        }
        $this->logger->info('User deletion: ' . $deleted . ' users deleted');

        // adhérents avec activité => on anonymise
        $usersToAnonymize = $this->userRepository->findUsersToAnonymize($endDateTime);
        $this->logger->info('User deletion: ' . count($usersToAnonymize) . ' users to anonymize');

        $anonymized = 0;
        /** @var User $user */
        foreach ($usersToAnonymize as $user) {
            // nettoyage des tables liées
            $this->userNiveauRepository->deleteByUser($user);
            $this->brevetAdherentRepository->deleteByUser($user);
            $this->userNotificationRepository->deleteByUser($user);
            $this->userAttrRepository->deleteByUser($user);

            $this->userRepository->anonymizeUser($user);
            ++$anonymized;
        }
        $this->logger->info('User deletion: ' . $anonymized . ' users anonymized');

        $this->manager->flush();

        $this->logger->info('User deletion: no (more) users to delete or anonymize');

        return Command::SUCCESS;
    }
}
