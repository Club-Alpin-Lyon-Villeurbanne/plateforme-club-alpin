<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;

class EmailMarketingSyncService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?MailerLiteService $mailerLiteService = null,
    ) {
    }

    /**
     * Synchroniser un ou plusieurs utilisateurs avec les services de marketing.
     *
     * @param User|array $users Un utilisateur ou un tableau d'utilisateurs
     */
    public function syncUsers(User|array $users): void
    {
        // Normaliser l'entrée en tableau
        $usersArray = \is_array($users) ? $users : [$users];

        if (empty($usersArray)) {
            return;
        }

        // Ne synchroniser que si le service MailerLite est configuré
        if (!$this->mailerLiteService) {
            $this->logger->debug('Email marketing sync skipped: MailerLite service not configured');

            return;
        }

        // Filtrer les utilisateurs sans email
        $usersWithEmail = array_filter($usersArray, fn (User $user) => !empty($user->getEmail()));

        if (empty($usersWithEmail)) {
            $this->logger->info('No users with email to sync');

            return;
        }

        $this->logger->info(sprintf('Synchronizing %d user(s) with email marketing services', \count($usersWithEmail)));

        // Synchroniser avec MailerLite
        if ($this->mailerLiteService) {
            try {
                $results = $this->mailerLiteService->syncNewMembers($usersWithEmail);
                $this->logger->info(sprintf(
                    'MailerLite sync: %d imported, %d updated, %d failed',
                    $results['imported'] ?? 0,
                    $results['updated'] ?? 0,
                    $results['failed'] ?? 0
                ));
            } catch (\Exception $e) {
                $this->logger->error('MailerLite sync failed: ' . $e->getMessage());
            }
        }
    }
}
