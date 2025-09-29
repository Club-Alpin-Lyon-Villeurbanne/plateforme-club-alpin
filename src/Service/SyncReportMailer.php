<?php

namespace App\Service;

use App\Mailer\Mailer;
use Psr\Log\LoggerInterface;

class SyncReportMailer
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $adminEmail,
    ) {
    }

    public function sendSyncReport(array $stats, \DateTime $startTime, \DateTime $endTime): void
    {
        try {
            $duration = $endTime->diff($startTime);

            $context = [
                'stats' => $stats,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'duration' => $duration->format('%H:%I:%S'),
                'date' => $startTime->format('d/m/Y'),
                'total' => $stats['inserted'] + $stats['updated'] + $stats['merged'],
            ];

            // Envoyer aux administrateurs
            $recipients = $this->getAdminRecipients();

            $this->mailer->send(
                $recipients,
                'transactional/sync_report',
                $context
            );

            $this->logger->info('Sync report email sent to administrators', [
                'recipients' => count($recipients),
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send sync report email', [
                'error' => $e->getMessage(),
                'stats' => $stats,
            ]);
        }
    }

    private function getAdminRecipients(): array
    {
        // Pour l'instant on utilise l'email admin configuré
        // On pourrait étendre pour récupérer tous les admins depuis la base
        $recipients = [];

        if (!empty($this->adminEmails)) {
            // Support de plusieurs emails séparés par des virgules
            $emails = array_map('trim', explode(',', $this->adminEmail));
            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = $email;
                }
            }
        }

        return $recipients;
    }
}
