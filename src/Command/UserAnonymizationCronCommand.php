<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\BrevetAdherentRepository;
use App\Repository\UserAttrRepository;
use App\Repository\UserNotificationRepository;
use App\Repository\UserRepository;
use App\Service\UserLicenseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'user-deletion-cron',
    description: 'Anonymisation des adhérents ayant une licence trop ancienne'
)]
class UserAnonymizationCronCommand extends Command
{
    public function __construct(
        protected UserRepository $userRepository,
        protected BrevetAdherentRepository $brevetAdherentRepository,
        protected UserNotificationRepository $userNotificationRepository,
        protected UserAttrRepository $userAttrRepository,
        protected UserLicenseHelper $userLicenseHelper,
        protected LoggerInterface $logger,
        protected readonly EntityManagerInterface $manager,
        protected ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('User deletion: find users to anonymize');

        $filesystem = new Filesystem();

        // date max d'adhésion qu'on conserve : 31/08 de la saison N-2
        // ex : pour la saison 2026-2027 (1ère occurrence de ce traitement), on conserve jusqu'au 31/08/2024 inclus
        $endDateTime = $this->userLicenseHelper->getLicenseExpirationDate(2);

        // adhérents sans activité
        $usersWithoutActivity = $this->userRepository->findUsersWithoutActivity($endDateTime);
        $this->logger->info('User anonymization: ' . count($usersWithoutActivity) . ' users without activity');

        // adhérents avec activité
        $usersWithActivity = $this->userRepository->findUsersWithActivity($endDateTime);
        $this->logger->info('User anonymization: ' . count($usersWithActivity) . ' users with activity');

        $anonymized = 0;
        $usersToAnonymize = array_merge($usersWithoutActivity, $usersWithoutActivity);

        /** @var User $user */
        foreach ($usersToAnonymize as $user) {
            // nettoyage des tables liées
            $this->brevetAdherentRepository->deleteByUser($user);
            $this->userNotificationRepository->deleteByUser($user);
            $this->userAttrRepository->deleteByUser($user);

            $this->userRepository->anonymizeUser($user);

            // image de profil
            $filesystem->remove($this->params->get('kernel.project_dir') . '/public/ftp/user/' . $user->getId());

            ++$anonymized;
        }
        $this->logger->info('User anonymization: ' . $anonymized . ' users anonymized');

        $this->manager->flush();

        $this->logger->info('User anonymization: no (more) users to anonymize');

        return Command::SUCCESS;
    }
}
