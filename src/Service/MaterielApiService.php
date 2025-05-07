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
        return strtoupper(substr($firstName, 0, 1) . '.' . $lastName);
    }

    /**
     * Create a new user in the Loxya system.
     */
    public function createUser(User $user): array
    {
        if (!$this->jwtToken) {
            $this->authenticate();
        }

        $pseudo = $this->generatePseudo($user->getFirstname(), $user->getLastname());
        $password = $this->generatePassword();

        try {
            $this->logger->info('Création d\'un utilisateur dans l\'API Loxya', [
                'email' => $user->getEmail(),
                'pseudo' => $pseudo,
            ]);

            $response = $this->client->request('POST', $this->apiBaseUrl . '/api/users', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                    'accept' => 'application/json',
                ],
                'json' => [
                    'first_name' => $user->getFirstname(),
                    'last_name' => $user->getLastname(),
                    'pseudo' => $pseudo,
                    'email' => $user->getEmail(),
                    'phone' => '',
                    'password' => $password,
                    'group' => 'readonly-planning-general',
                    'restricted_parks' => [],
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if (Response::HTTP_CREATED === $statusCode) {
                $userData = $response->toArray();
                $this->logger->info('Utilisateur créé avec succès', [
                    'pseudo' => $pseudo,
                ]);

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

            $this->logger->error('Échec de la création de l\'utilisateur', [
                'statusCode' => $statusCode,
                'response' => $response->getContent(false),
            ]);
            throw new \RuntimeException('Failed to create user: ' . $response->getContent(false));
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de l\'utilisateur', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to create user: ' . $e->getMessage());
        }
    }

    public function userExists(User $user): bool
    {
        return $user->hasMaterielAccount();
    }
}
