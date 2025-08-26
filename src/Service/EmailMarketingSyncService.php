<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;

class EmailMarketingSyncService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?MailerLiteService $mailerLiteService = null,
        private readonly ?MailchimpService $mailchimpService = null,
    ) {
    }

    /**
     * Synchroniser un ou plusieurs utilisateurs avec les services de marketing.
     *
     * @param User|array $users Un utilisateur ou un tableau d'utilisateurs
     *
     * @return array Résultats de la synchronisation
     */
    public function syncUsers(User|array $users): array
    {
        // Normaliser l'entrée en tableau
        $usersArray = \is_array($users) ? $users : [$users];

        if (empty($usersArray)) {
            return $this->createEmptyResults();
        }

        // Ne synchroniser que si au moins un service est configuré
        if (!$this->mailerLiteService && !$this->mailchimpService) {
            $this->logger->debug('Email marketing sync skipped: no service configured');

            return $this->createEmptyResults();
        }

        // Filtrer les utilisateurs sans email
        $usersWithEmail = array_filter($usersArray, fn (User $user) => !empty($user->getEmail()));

        if (empty($usersWithEmail)) {
            $this->logger->info('No users with email to sync');

            return $this->createEmptyResults();
        }

        $this->logger->info(sprintf('Synchronizing %d user(s) with email marketing services', \count($usersWithEmail)));

        $results = [
            'mailerlite' => null,
            'mailchimp' => null,
        ];

        // Synchroniser avec MailerLite
        if ($this->mailerLiteService) {
            try {
                $results['mailerlite'] = $this->mailerLiteService->syncNewMembers($usersWithEmail);
                $this->logger->info(sprintf(
                    'MailerLite sync: %d imported, %d updated, %d failed',
                    $results['mailerlite']['imported'] ?? 0,
                    $results['mailerlite']['updated'] ?? 0,
                    $results['mailerlite']['failed'] ?? 0
                ));
            } catch (\Exception $e) {
                $this->logger->error('MailerLite sync failed: ' . $e->getMessage());
                $results['mailerlite'] = ['error' => $e->getMessage()];
            }
        }

        // Synchroniser avec Mailchimp
        if ($this->mailchimpService) {
            try {
                $results['mailchimp'] = $this->mailchimpService->syncNewMembers($usersWithEmail);
                $this->logger->info(sprintf(
                    'Mailchimp sync: %d imported, %d updated, %d failed',
                    $results['mailchimp']['imported'] ?? 0,
                    $results['mailchimp']['updated'] ?? 0,
                    $results['mailchimp']['failed'] ?? 0
                ));
            } catch (\Exception $e) {
                $this->logger->error('Mailchimp sync failed: ' . $e->getMessage());
                $results['mailchimp'] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Synchroniser un utilisateur qui vient d'activer son compte.
     * Cette méthode est spécifiquement pour les activations de compte individuelles.
     */
    public function syncActivatedUser(User $user): array
    {
        if (!$user->getEmail()) {
            $this->logger->debug(sprintf('User %s has no email, skipping marketing sync', $user->getCafnum()));

            return $this->createEmptyResults();
        }

        $this->logger->info(sprintf(
            'Syncing newly activated user %s (%s) with email marketing services',
            $user->getCafnum(),
            $user->getEmail()
        ));

        return $this->syncUsers($user);
    }

    /**
     * Créer un résultat vide pour la cohérence.
     */
    private function createEmptyResults(): array
    {
        return [
            'mailerlite' => null,
            'mailchimp' => null,
        ];
    }
}
