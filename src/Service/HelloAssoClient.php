<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloAssoClient
{
    public const string HELLO_ASSO_TOKEN_ENDPOINT = '/oauth2/token';

    public function __construct(
        protected string $clientId,
        protected string $clientSecret,
        protected string $baseUrl,
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger
    ) {
    }

    public function login(): string
    {
        $accessToken = '';

        try {
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
            $data = $response->toArray();
            $accessToken = $data['access_token'];
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $accessToken;
    }
}
