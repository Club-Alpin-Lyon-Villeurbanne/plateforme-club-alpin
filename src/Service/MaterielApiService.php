<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MaterielApiService
{
    private string $apiBaseUrl;
    private string $apiUsername;
    private string $apiPassword;
    private ?string $jwtToken = null;
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        string $apiBaseUrl = '',
        string $apiUsername = '',
        string $apiPassword = ''
    ) {
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
        $this->client = HttpClient::create();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * Authenticate with the API and get JWT token.
     */
    public function authenticate(): void
    {
        try {
            $this->logger->info('Authentification à l\'API Loxya', [
                'url' => $this->apiBaseUrl . '/api/session',
            ]);

            $response = $this->client->request('POST', $this->apiBaseUrl . '/api/session', [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'identifier' => $this->apiUsername,
                    'password' => $this->apiPassword,
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if (Response::HTTP_OK === $statusCode) {
                $data = $response->toArray();
                $this->jwtToken = $data['token'];
                $this->logger->info('Authentification réussie');
            } else {
                $this->logger->error('Échec de l\'authentification', [
                    'statusCode' => $statusCode,
                    'response' => $response->getContent(false),
                ]);
                throw new \RuntimeException('Failed to authenticate with Loxya API: Invalid response code ' . $statusCode);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur d\'authentification', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to authenticate with Loxya API: ' . $e->getMessage());
        }
    }

    /**
     * Get the JWT token.
     */
    public function getJwtToken(): ?string
    {
        return $this->jwtToken;
    }

    /**
     * Generate a random password.
     */
    private function generatePassword(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
        $password = '';
        $length = 12;

        for ($i = 0; $i < $length; ++$i) {
            $password .= $chars[random_int(0, \strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * Generate a pseudo from first name and last name.
     */
    private function generatePseudo(string $firstName, string $lastName): string
    {
        return strtolower(preg_replace('/[^a-zA-Z]/', '', $firstName . $lastName));
    }

    /**
     * Create a new beneficiary in the Loxya system.
     */
    public function createUser(User $user): array
    {
        if (!$this->jwtToken) {
            $this->authenticate();
        }

        $pseudo = $this->generatePseudo($user->getFirstname(), $user->getLastname());
        $password = $this->generatePassword();

        try {
            $this->logger->info('Création d\'un bénéficiaire dans l\'API Loxya', [
                'email' => $user->getEmail(),
                'pseudo' => $pseudo,
            ]);

            $response = $this->client->request('POST', $this->apiBaseUrl . '/api/beneficiaries', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                ],
                'json' => [
                    'first_name' => $user->getFirstname(),
                    'last_name' => $user->getLastname(),
                    'can_make_reservation' => true,
                    'email' => $user->getEmail(),
                    'pseudo' => $pseudo,
                    'password' => $password,
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if (Response::HTTP_CREATED === $statusCode) {
                $userData = $response->toArray();
                $this->logger->info('Bénéficiaire créé avec succès', [
                    'pseudo' => $pseudo,
                ]);

                // Mettre à jour les droits utilisateur pour permettre l'accès au planning
                $this->updateUserGroup($userData['id'], $pseudo, $user->getFirstname(), $user->getLastname(), $user->getEmail());

                // Mettre à jour le statut dans la base de données locale
                $user->setMaterielAccountCreatedAt(new \DateTime());
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return [
                    'email' => $user->getEmail(),
                    'password' => $password,
                    'pseudo' => $pseudo,
                ];
            }

            $this->logger->error('Échec de la création du bénéficiaire', [
                'statusCode' => $statusCode,
                'response' => $response->getContent(false),
            ]);
            throw new \RuntimeException('Failed to create beneficiary: ' . $response->getContent(false));
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du bénéficiaire', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to create beneficiary: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour le groupe d'un utilisateur pour lui donner accès au planning.
     */
    private function updateUserGroup(int $userId, string $pseudo, string $firstName, string $lastName, string $email): void
    {
        if (!$this->jwtToken) {
            $this->authenticate();
        }

        try {
            $this->logger->info('Mise à jour des droits utilisateur', [
                'userId' => $userId,
                'pseudo' => $pseudo,
            ]);

            $response = $this->client->request('PUT', $this->apiBaseUrl . '/api/users/' . $userId, [
                'headers' => [
                    'accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                ],
                'json' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'pseudo' => $pseudo,
                    'email' => $email,
                    'phone' => null,
                    'password' => '',
                    'group' => 'readonly-planning-self',
                    'restricted_parks' => [],
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if (Response::HTTP_OK === $statusCode) {
                $this->logger->info('Droits utilisateur mis à jour avec succès', [
                    'userId' => $userId,
                    'group' => 'readonly-planning-self',
                ]);
            } else {
                $this->logger->error('Échec de la mise à jour des droits utilisateur', [
                    'statusCode' => $statusCode,
                    'response' => $response->getContent(false),
                ]);
                throw new \RuntimeException('Failed to update user group: ' . $response->getContent(false));
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour des droits utilisateur', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to update user group: ' . $e->getMessage());
        }
    }

    public function userExists(User $user): bool
    {
        return $user->hasMaterielAccount();
    }

    /**
     * Vérifie si un utilisateur existe déjà sur Loxya via l'API externe.
     */
    public function userExistsOnLoxya(User $user): bool
    {
        if (!$this->jwtToken) {
            $this->authenticate();
        }

        $email = $user->getEmail();
        $url = $this->apiBaseUrl . '/api/users?page=1&limit=100&ascending=1&search[]=' . urlencode($email) . '&deleted=0';

        try {
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if (200 !== $statusCode) {
                $this->logger->error('Erreur lors de la vérification de l\'existence utilisateur sur Loxya', [
                    'statusCode' => $statusCode,
                    'response' => $response->getContent(false),
                ]);

                return false;
            }

            $data = $response->toArray();
            $users = $data['data'] ?? $data;

            return !empty($users);
        } catch (\Exception $e) {
            $this->logger->error('Exception lors de la vérification de l\'existence utilisateur sur Loxya', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
