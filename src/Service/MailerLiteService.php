<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MailerLiteService
{
    private const API_URL = 'https://connect.mailerlite.com/api';
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly ?string $apiKey = null,
        private readonly ?string $welcomeGroupId = null,
    ) {
    }

    /**
     * Synchroniser tous les nouveaux membres en masse.
     */
    public function syncNewMembers(array $users): array
    {
        $results = [
            'total' => \count($users),
            'imported' => 0,
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        // Vérifier la configuration
        if (!$this->apiKey || !$this->welcomeGroupId) {
            $this->logger->info('MailerLite sync disabled: missing API key or group ID', [
                'hasApiKey' => !empty($this->apiKey),
                'hasGroupId' => !empty($this->welcomeGroupId),
                'apiKeyLength' => \strlen($this->apiKey ?? ''),
                'groupId' => $this->welcomeGroupId,
            ]);
            $results['skipped'] = \count($users);

            return $results;
        }

        $this->logger->info('MailerLite configuration OK', [
            'apiKeyLength' => \strlen($this->apiKey),
            'groupId' => $this->welcomeGroupId,
            'usersCount' => \count($users),
        ]);

        // Filtrer les users sans email
        $usersWithEmail = array_filter($users, fn (User $user) => $user->getEmail());
        $results['skipped'] = \count($users) - \count($usersWithEmail);

        if (empty($usersWithEmail)) {
            $this->logger->info('No users with email to sync to MailerLite');

            return $results;
        }

        // Traiter par batches pour respecter les limites de l'API
        $batches = array_chunk($usersWithEmail, self::BATCH_SIZE);

        foreach ($batches as $batchIndex => $batch) {
            $this->logger->info(sprintf('Processing MailerLite batch %d/%d', $batchIndex + 1, \count($batches)), [
                'batchSize' => \count($batch),
                'batchEmails' => array_map(fn ($user) => $user->getEmail(), $batch),
            ]);

            $batchResults = $this->importBatch($batch);

            if ($batchResults) {
                $results['imported'] += $batchResults['imported'] ?? 0;
                $results['updated'] += $batchResults['updated'] ?? 0;
                $results['failed'] += $batchResults['failed'] ?? 0;
            } else {
                $results['failed'] += \count($batch);
            }

            // Pause entre les batches pour éviter le rate limiting
            if ($batchIndex < \count($batches) - 1) {
                sleep(1);
            }
        }

        $this->logger->info(sprintf(
            'MailerLite sync completed: %d imported, %d updated, %d failed, %d skipped (total: %d)',
            $results['imported'],
            $results['updated'],
            $results['failed'],
            $results['skipped'],
            $results['total']
        ));

        return $results;
    }

    /**
     * Importer un batch de membres dans le groupe de bienvenue.
     */
    private function importBatch(array $users): ?array
    {
        $subscribers = [];

        foreach ($users as $user) {
            $subscribers[] = [
                'email' => $user->getEmail(),
                'fields' => [
                    'name' => $user->getFirstname(),
                    'last_name' => $user->getLastname(),
                ],
            ];
        }

        try {
            $this->logger->info('Making MailerLite API request', [
                'url' => self::API_URL . '/groups/' . $this->welcomeGroupId . '/subscribers/import',
                'subscribersCount' => \count($subscribers),
                'firstEmail' => $subscribers[0]['email'] ?? 'none',
            ]);

            $response = $this->httpClient->request(
                'POST',
                self::API_URL . '/groups/' . $this->welcomeGroupId . '/subscribers/import',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'subscribers' => $subscribers,
                        'resubscribe' => false,
                        'autoresponders' => true,
                    ],
                ]
            );

            if (200 === $response->getStatusCode() || 201 === $response->getStatusCode()) {
                $responseData = $response->toArray();
                $this->logger->info('MailerLite API response success', [
                    'statusCode' => $response->getStatusCode(),
                    'responseData' => $responseData,
                ]);

                return $responseData;
            }

            $this->logger->error('MailerLite import failed', [
                'statusCode' => $response->getStatusCode(),
                'responseBody' => $response->getContent(false),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logger->error('MailerLite API error during import: ' . $e->getMessage());

            return null;
        }
    }
}
