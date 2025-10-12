<?php

namespace App\Service;

use App\Mailer\Mailer;
use Psr\Log\LoggerInterface;

class FfcamSyncReportMailer
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $adminEmails,
    ) {
    }

    public function sendSyncReport(array $stats, \DateTime $startTime, \DateTime $endTime): void
    {
        try {
            // Récupérer les destinataires dès le début
            $recipients = $this->getAdminRecipients();

            // Si aucun destinataire configuré, ne rien envoyer
            if (empty($recipients)) {
                $this->logger->info('Sync report not sent: no recipients configured (SYNC_REPORT_RECIPIENTS is empty)');
                return;
            }

            $duration = $endTime->diff($startTime);

            // Limiter les détails pour l'email (garder tous les détails dans $stats pour debug)
            $limitedStats = $stats;
            if (isset($limitedStats['merged_details'])) {
                $limitedStats['merged_details'] = array_slice($limitedStats['merged_details'], 0, 20);
            }
            if (isset($limitedStats['error_details'])) {
                $limitedStats['error_details'] = array_slice($limitedStats['error_details'], 0, 10);
            }

            $context = [
                'stats' => $limitedStats,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'duration' => $duration->format('%H:%I:%S'),
                'date' => $startTime->format('d/m/Y'),
                'total' => $stats['inserted'] + $stats['updated'] + $stats['merged'],
            ];

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
            $emails = array_map('trim', explode(',', $this->adminEmails));
            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = $email;
                }
            }
        }

        return $recipients;
    }
}
