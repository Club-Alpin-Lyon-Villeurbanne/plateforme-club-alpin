<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MailchimpService
{
    private const BATCH_SIZE = 500;
    private ?string $apiUrl = null;
    
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly ?string $apiKey = null,
        private readonly ?string $listId = null,
    ) {
        if ($this->apiKey) {
            $parts = explode('-', $this->apiKey);
            $this->apiUrl = sprintf('https://%s.api.mailchimp.com/3.0', $parts[1] ?? 'us1');
        }
    }

    /**
     * Synchroniser tous les nouveaux membres avec Mailchimp
     */
    public function syncNewMembers(array $users): array
    {
        $results = [
            'total' => count($users),
            'imported' => 0,
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];
        
        if (!$this->apiUrl || !$this->listId) {
            $this->logger->info('Mailchimp sync disabled or not configured');
            $results['skipped'] = count($users);
            return $results;
        }
        
        // Filtrer les users sans email
        $usersWithEmail = array_filter($users, fn(User $user) => $user->getEmail());
        $results['skipped'] = count($users) - count($usersWithEmail);
        
        if (empty($usersWithEmail)) {
            $this->logger->info('No users with email to sync to Mailchimp');
            return $results;
        }
        
        // Traiter par batches
        $batches = array_chunk($usersWithEmail, self::BATCH_SIZE);
        
        foreach ($batches as $batchIndex => $batch) {
            $this->logger->info(sprintf('Processing Mailchimp batch %d/%d', $batchIndex + 1, count($batches)));
            
            $batchResults = $this->processBatch($batch);
            
            if ($batchResults) {
                $results['imported'] += $batchResults['new_members'] ?? 0;
                $results['updated'] += $batchResults['updated_members'] ?? 0;
                $results['failed'] += $batchResults['error_count'] ?? 0;
            } else {
                $results['failed'] += count($batch);
            }
            
            // Pause entre les batches pour éviter le rate limiting
            if ($batchIndex < count($batches) - 1) {
                sleep(1);
            }
        }
        
        $this->logger->info(sprintf(
            'Mailchimp sync completed: %d imported, %d updated, %d failed, %d skipped (total: %d)',
            $results['imported'],
            $results['updated'],
            $results['failed'],
            $results['skipped'],
            $results['total']
        ));
        
        return $results;
    }
    
    /**
     * Traiter un batch de membres
     */
    private function processBatch(array $users): ?array
    {
        $members = [];
        
        foreach ($users as $user) {
            $members[] = [
                'email_address' => $user->getEmail(),
                'status' => 'subscribed',
                'merge_fields' => [
                    'FNAME' => $user->getFirstname() ?? '',
                    'LNAME' => $user->getLastname() ?? '',
                    'CAFNUM' => $user->getCafnum() ?? '',
                    'CITY' => $user->getVille() ?? '',
                    'ZIP' => $user->getCp() ?? '',
                ],
                'timestamp_signup' => $user->getDateAdhesion()?->format('c') ?? (new \DateTime())->format('c'),
            ];
        }
        
        try {
            // Utiliser l'endpoint de batch subscribe
            $response = $this->httpClient->request(
                'POST',
                $this->apiUrl . '/lists/' . $this->listId,
                [
                    'auth_basic' => ['anystring', $this->apiKey],
                    'json' => [
                        'members' => $members,
                        'update_existing' => true, // Mettre à jour les membres existants
                        'skip_merge_validation' => false,
                    ],
                ]
            );
            
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                
                $this->logger->info(sprintf(
                    'Mailchimp batch processed: %d new, %d updated, %d errors',
                    $data['new_members'] ?? 0,
                    $data['updated_members'] ?? 0,
                    $data['error_count'] ?? 0
                ));
                
                // Log des erreurs si présentes
                if (!empty($data['errors'])) {
                    foreach ($data['errors'] as $error) {
                        $this->logger->warning(sprintf(
                            'Mailchimp error for %s: %s',
                            $error['email_address'] ?? 'unknown',
                            $error['error'] ?? 'unknown error'
                        ));
                    }
                }
                
                return $data;
            }
            
            $this->logger->error('Mailchimp batch failed with status: ' . $response->getStatusCode());
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Mailchimp API error: ' . $e->getMessage());
            return null;
        }
    }
    
}