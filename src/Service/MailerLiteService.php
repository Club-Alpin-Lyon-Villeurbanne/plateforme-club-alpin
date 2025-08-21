<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MailerLiteService
{
    private const API_URL = 'https://connect.mailerlite.com/api';
    private const BATCH_SIZE = 100; // MailerLite recommande des batches de max 100-500
    
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly string $welcomeGroupId,
    ) {
    }

    /**
     * Ajoute un nouvel adhérent à MailerLite et l'ajoute au groupe de bienvenue
     */
    public function addNewMember(User $user): bool
    {
        try {
            // Créer ou mettre à jour le subscriber
            $subscriberData = $this->createOrUpdateSubscriber($user);
            
            if (!$subscriberData) {
                return false;
            }
            
            $subscriberId = $subscriberData['data']['id'] ?? null;
            
            if (!$subscriberId) {
                $this->logger->error('MailerLite: No subscriber ID returned for user ' . $user->getId());
                return false;
            }
            
            // Ajouter au groupe de bienvenue
            return $this->addToWelcomeGroup($subscriberId, $user);
            
        } catch (\Exception $e) {
            $this->logger->error('MailerLite error for user ' . $user->getId() . ': ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Créer ou mettre à jour un subscriber dans MailerLite
     */
    private function createOrUpdateSubscriber(User $user): ?array
    {
        $email = $user->getEmail();
        
        if (!$email) {
            $this->logger->warning('User ' . $user->getId() . ' has no email, skipping MailerLite sync');
            return null;
        }
        
        $subscriberData = [
            'email' => $email,
            'fields' => [
                'name' => $user->getFirstname(),
                'last_name' => $user->getLastname(),
                'caf_number' => $user->getCafnum(),
                'city' => $user->getVille(),
                'postal_code' => $user->getCp(),
                'registration_date' => $user->getDateAdhesion()?->format('Y-m-d'),
            ],
            'status' => 'active',
        ];
        
        try {
            $response = $this->httpClient->request('POST', self::API_URL . '/subscribers', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $subscriberData,
            ]);
            
            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $this->logger->info('MailerLite: Subscriber created/updated for user ' . $user->getId());
                return $response->toArray();
            }
            
            // Si le subscriber existe déjà (status 409), on le récupère
            if ($response->getStatusCode() === 409) {
                return $this->getSubscriberByEmail($email);
            }
            
            $this->logger->error('MailerLite: Failed to create subscriber for user ' . $user->getId() . ', status: ' . $response->getStatusCode());
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('MailerLite API error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer un subscriber par email
     */
    private function getSubscriberByEmail(string $email): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL . '/subscribers/' . urlencode($email), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);
            
            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('MailerLite API error getting subscriber: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ajouter un subscriber au groupe de bienvenue
     */
    private function addToWelcomeGroup(string $subscriberId, User $user): bool
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL . '/subscribers/' . $subscriberId . '/groups/' . $this->welcomeGroupId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);
            
            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 204) {
                $this->logger->info('MailerLite: User ' . $user->getId() . ' added to welcome group');
                return true;
            }
            
            $this->logger->error('MailerLite: Failed to add user ' . $user->getId() . ' to welcome group, status: ' . $response->getStatusCode());
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('MailerLite API error adding to group: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Synchroniser tous les nouveaux membres en masse
     * Utilise l'endpoint d'import en bulk qui est plus efficace
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
        
        // Filtrer les users sans email
        $usersWithEmail = array_filter($users, fn(User $user) => $user->getEmail());
        $results['skipped'] = count($users) - count($usersWithEmail);
        
        if (empty($usersWithEmail)) {
            $this->logger->info('No users with email to sync to MailerLite');
            return $results;
        }
        
        // Traiter par batches pour respecter les limites de l'API
        $batches = array_chunk($usersWithEmail, self::BATCH_SIZE);
        
        foreach ($batches as $batchIndex => $batch) {
            $this->logger->info(sprintf('Processing batch %d/%d', $batchIndex + 1, count($batches)));
            
            $batchResults = $this->importBatch($batch);
            
            if ($batchResults) {
                $results['imported'] += $batchResults['imported'] ?? 0;
                $results['updated'] += $batchResults['updated'] ?? 0;
                $results['failed'] += $batchResults['failed'] ?? 0;
            } else {
                $results['failed'] += count($batch);
            }
            
            // Pause entre les batches pour éviter le rate limiting
            if ($batchIndex < count($batches) - 1) {
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
     * Importer un batch de membres dans le groupe de bienvenue
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
                    'caf_number' => $user->getCafnum(),
                    'city' => $user->getVille(),
                    'postal_code' => $user->getCp(),
                    'registration_date' => $user->getDateAdhesion()?->format('Y-m-d'),
                ],
            ];
        }
        
        try {
            // Importer les subscribers dans le groupe de bienvenue
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
                        'resubscribe' => false, // Ne pas réinscrire les désinscrits
                        'autoresponders' => true, // Activer les autorépondeurs (mail de bienvenue)
                    ],
                ]
            );
            
            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $data = $response->toArray();
                
                // Si on a un ID d'import, on peut suivre le progrès
                if (isset($data['id'])) {
                    return $this->checkImportProgress($data['id']);
                }
                
                return $data;
            }
            
            $this->logger->error('MailerLite import failed with status: ' . $response->getStatusCode());
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('MailerLite API error during import: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifier le progrès d'un import
     */
    private function checkImportProgress(string $importId): ?array
    {
        $maxAttempts = 30;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            try {
                $response = $this->httpClient->request(
                    'GET',
                    self::API_URL . '/batch/' . $importId,
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Accept' => 'application/json',
                        ],
                    ]
                );
                
                if ($response->getStatusCode() === 200) {
                    $data = $response->toArray();
                    
                    // Si l'import est terminé
                    if (isset($data['status']) && in_array($data['status'], ['done', 'failed'])) {
                        return [
                            'imported' => $data['statistics']['imported'] ?? 0,
                            'updated' => $data['statistics']['updated'] ?? 0,
                            'failed' => $data['statistics']['errored'] ?? 0,
                        ];
                    }
                }
                
            } catch (\Exception $e) {
                $this->logger->warning('Error checking import progress: ' . $e->getMessage());
            }
            
            $attempt++;
            sleep(2); // Attendre 2 secondes avant de réessayer
        }
        
        $this->logger->warning('Import progress check timed out for import ' . $importId);
        return null;
    }
}