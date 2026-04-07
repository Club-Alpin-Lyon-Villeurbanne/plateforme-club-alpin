<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloAssoClient
{
    public const string HELLO_ASSO_TOKEN_ENDPOINT = '/oauth2/token';
    public const string HELLO_ASSO_CHECKOUT_INTENT_ENDPOINT = '/v5/organizations/{organizationSlug}/checkout-intents';

    public function __construct(
        protected string $clientId,
        protected string $clientSecret,
        protected string $baseUrl,
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger,
        protected readonly CacheInterface $cache,
    ) {
    }

    public function login(): string
    {
        return $this->cache->get('helloasso_access_token', function (ItemInterface $item): string {
            $response = $this->httpClient->request(
                'POST',
                $this->baseUrl . self::HELLO_ASSO_TOKEN_ENDPOINT,
                [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'accept' => 'application/json',
                    ],
                    'body' => [
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'grant_type' => 'client_credentials',
                    ],
                ],
            );

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException('HelloAsso login failed: ' . $response->getContent(false));
            }

            $data = $response->toArray();

            if (empty($data['access_token'])) {
                throw new \RuntimeException('HelloAsso login failed: no access_token in response');
            }

            $item->expiresAfter(max(($data['expires_in'] ?? 1800) - 60, 0));

            return $data['access_token'];
        });
    }

    public function createForm(string $apiEndpoint, array $params = []): array
    {
        $token = $this->login();

        $response = $this->httpClient->request(
            'POST',
            $this->baseUrl . $apiEndpoint,
            [
                'headers' => [
                    'Content-Type' => 'application/*+json',
                    'accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => $params,
            ],
        );

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('HelloAsso createForm failed: ' . $response->getContent(false));
        }

        return $response->toArray();
    }

    public function publishForm(string $apiEndpoint): void
    {
        $token = $this->login();

        $response = $this->httpClient->request(
            'PUT',
            $this->baseUrl . $apiEndpoint,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => [
                    'state' => 'Private',
                ],
            ],
        );

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('HelloAsso publishForm failed: ' . $response->getContent(false));
        }
    }

    public function createCheckoutIntent(string $organizationSlug, array $params): array
    {
        $token = $this->login();
        $endpoint = str_replace('{organizationSlug}', $organizationSlug, self::HELLO_ASSO_CHECKOUT_INTENT_ENDPOINT);

        $response = $this->httpClient->request(
            'POST',
            $this->baseUrl . $endpoint,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => $params,
            ],
        );

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('HelloAsso checkout intent failed: ' . $response->getContent(false));
        }

        return $response->toArray();
    }

    public function areCredentialsSet(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }
}
