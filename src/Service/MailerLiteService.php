<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MailerLiteService
{
    private const API_URL = 'https://connect.mailerlite.com/api';
    private const BATCH_SIZE = 100;
    private const DEDUP_EMAIL_PREFIX = 'doublon.';
    private const IMPORT_BATCH_DELAY_SECONDS = 3;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly ?string $apiKey = null,
        private readonly ?string $welcomeGroupId = null,
        private readonly string $deployEnv = 'development',
    ) {
    }

    /**
     * @param User[] $users
     *
     * @return array{total: int, imported: int, updated: int, failed: int, skipped: int}
     */
    public function syncNewMembers(array $users): array
    {
        return $this->pushToGroup((string) $this->welcomeGroupId, $users);
    }

    /**
     * @param User[] $users
     *
     * @return array{total: int, imported: int, updated: int, failed: int, skipped: int}
     */
    public function pushToGroup(string $groupId, array $users): array
    {
        $results = ['total' => \count($users), 'imported' => 0, 'updated' => 0, 'failed' => 0, 'skipped' => 0];

        if ('production' !== $this->deployEnv) {
            $this->logger->info('MailerLite sync ignoré hors production', ['deployEnv' => $this->deployEnv]);
            $results['skipped'] = \count($users);

            return $results;
        }

        if (!$this->apiKey || !$groupId) {
            $this->logger->info('MailerLite sync disabled: missing API key or group ID', [
                'hasApiKey' => !empty($this->apiKey),
                'hasGroupId' => !empty($groupId),
                'apiKeyLength' => \strlen($this->apiKey ?? ''),
                'groupId' => $groupId,
            ]);
            $results['skipped'] = \count($users);

            return $results;
        }

        $eligibles = array_filter($users, fn (User $user) => $this->hasUsableEmail($user));
        $results['skipped'] = \count($users) - \count($eligibles);

        if (empty($eligibles)) {
            return $results;
        }

        $batches = array_chunk(array_values($eligibles), self::BATCH_SIZE);

        foreach ($batches as $index => $batch) {
            $batchResults = $this->importBatch($groupId, $batch);

            if (null === $batchResults) {
                $results['failed'] += \count($batch);
            } else {
                $results['imported'] += $batchResults['imported'] ?? 0;
                $results['updated'] += $batchResults['updated'] ?? 0;
                $results['failed'] += $batchResults['errored'] ?? 0;
            }

            if ($index < \count($batches) - 1) {
                sleep(self::IMPORT_BATCH_DELAY_SECONDS);
            }
        }

        return $results;
    }

    public function removeFromGroup(string $email, string $groupId): bool
    {
        if ('production' !== $this->deployEnv || !$this->apiKey) {
            return true;
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL . '/subscribers/' . rawurlencode($email), [
                'headers' => $this->headers(),
            ]);

            if (404 === $response->getStatusCode()) {
                return true;
            }

            if (200 !== $response->getStatusCode()) {
                $this->logger->error('MailerLite : échec de la recherche d\'abonné', ['email' => $email, 'groupId' => $groupId, 'statusCode' => $response->getStatusCode()]);

                return false;
            }

            $data = $response->toArray(false)['data'] ?? [];
            $subscriberId = $data['id'] ?? null;
            $groupIds = array_map(fn (array $group) => (string) ($group['id'] ?? ''), $data['groups'] ?? []);

            if (!$subscriberId || !\in_array($groupId, $groupIds, true)) {
                return true;
            }

            $delete = $this->httpClient->request('DELETE', self::API_URL . '/subscribers/' . $subscriberId . '/groups/' . $groupId, [
                'headers' => $this->headers(),
            ]);

            return \in_array($delete->getStatusCode(), [200, 204], true);
        } catch (\Exception $e) {
            $this->logger->error('MailerLite : échec du retrait de groupe', ['email' => $email, 'groupId' => $groupId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function hasUsableEmail(User $user): bool
    {
        $email = (string) $user->getEmail();

        return '' !== $email && !str_starts_with($email, self::DEDUP_EMAIL_PREFIX);
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * @param User[] $users
     *
     * @return array<string, mixed>|null
     */
    private function importBatch(string $groupId, array $users): ?array
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
                'url' => self::API_URL . '/groups/' . $groupId . '/subscribers/import',
                'subscribersCount' => \count($subscribers),
                'firstEmail' => $subscribers[0]['email'] ?? 'none',
            ]);

            $response = $this->httpClient->request(
                'POST',
                self::API_URL . '/groups/' . $groupId . '/subscribers/import',
                [
                    'headers' => $this->headers(),
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

                $data = $responseData['data'] ?? null;

                if (!\is_array($data)) {
                    $this->logger->error('MailerLite import : réponse sans données exploitables', [
                        'responseData' => $responseData,
                    ]);

                    return null;
                }

                return $data;
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
