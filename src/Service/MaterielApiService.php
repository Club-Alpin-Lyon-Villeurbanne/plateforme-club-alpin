<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class MaterielApiService
{
    private string $apiBaseUrl;
    private string $apiUsername;
    private string $apiPassword;
    private ?string $jwtToken = null;
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        string $apiBaseUrl = '',
        string $apiUsername = '',
        string $apiPassword = ''
    ) {
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
        $this->client = HttpClient::create();
        $this->logger = $logger;
    }

    /**
     * Authenticate with the API and get JWT token
     */
    private function authenticate(): void
    {
        try {
            $this->logger->info('Attempting to authenticate with Loxya API', [
                'url' => $this->apiBaseUrl . '/api/session',
                'email' => $this->apiUsername
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
            $this->logger->info('Authentication response received', [
                'statusCode' => $statusCode,
                'email' => $this->apiUsername
            ]);

            if ($statusCode === Response::HTTP_OK) {
                $data = $response->toArray();
                $this->jwtToken = $data['token'];
                $this->logger->info('Successfully authenticated with Loxya API', [
                    'email' => $this->apiUsername
                ]);
            } else {
                $this->logger->error('Authentication failed', [
                    'statusCode' => $statusCode,
                    'response' => $response->getContent(false),
                    'email' => $this->apiUsername
                ]);
                throw new \RuntimeException('Failed to authenticate with Loxya API: Invalid response code ' . $statusCode);
            }
        } catch (\Exception $e) {
            $this->logger->error('Authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $this->apiUsername
            ]);
            throw new \RuntimeException('Failed to authenticate with Loxya API: ' . $e->getMessage());
        }
    }

    /**
     * Check if user exists in the Loxya system
     */
    public function checkUserExists(string $email): bool
    {
        if (!$this->jwtToken) {
            $this->authenticate();
        }

        try {
            $response = $this->client->request('GET', $this->apiBaseUrl . '/api/users', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                    'accept' => 'application/json',
                ],
                'query' => [
                    'page' => 1,
                    'limit' => 100,
                    'ascending' => 1,
                    'deleted' => 0,
                ],
            ]);

            $this->logger->info('API response received for user check', [
                'statusCode' => $response->getStatusCode(),
                'content' => $response->getContent(false)
            ]);

            if ($response->getStatusCode() === Response::HTTP_OK) {
                $users = $response->toArray()['data'];
                foreach ($users as $user) {
                    if ($user['email'] === $email) {
                        return true;
                    }
                }
            }
            return false;
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface $e) {
            throw new \RuntimeException('Failed to check user existence: ' . $e->getMessage());
        }
    }

    /**
     * Generate a random password
     */
    private function generatePassword(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
        $password = '';
        $length = 12;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * Generate a pseudo from first name and last name
     */
    private function generatePseudo(string $firstName, string $lastName): string
    {
        return strtoupper(substr($firstName, 0, 1) . '.' . $lastName);
    }

    /**
     * Create a new user in the Loxya system
     */
    public function createUser(string $email, string $firstName, string $lastName): array
    {
        if (!$this->jwtToken) {
            $this->authenticate();
        }

        $pseudo = $this->generatePseudo($firstName, $lastName);
        $password = $this->generatePassword();

        try {
            $this->logger->info('Attempting to create user in Loxya API', [
                'email' => $email,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'pseudo' => $pseudo
            ]);

            $response = $this->client->request('POST', $this->apiBaseUrl . '/api/users', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                    'accept' => 'application/json',
                ],
                'json' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'pseudo' => $pseudo,
                    'email' => $email,
                    'phone' => '',
                    'password' => $password,
                    'group' => 'readonly-planning-general',
                    'restricted_parks' => [],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info('Create user response received', [
                'statusCode' => $statusCode
            ]);

            if ($statusCode === Response::HTTP_CREATED) {
                $userData = $response->toArray();
                $this->logger->info('User created successfully', [
                    'pseudo' => $pseudo
                ]);
                return [
                    'email' => $email,
                    'password' => $password,
                    'pseudo' => $pseudo,
                ];
            }

            $this->logger->error('Failed to create user', [
                'statusCode' => $statusCode,
                'response' => $response->getContent(false)
            ]);
            throw new \RuntimeException('Failed to create user: ' . $response->getContent(false));
        } catch (\Exception $e) {
            $this->logger->error('Error creating user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to create user: ' . $e->getMessage());
        }
    }
} 