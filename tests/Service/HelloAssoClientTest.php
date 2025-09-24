<?php

namespace App\Tests\Service;

use App\Service\HelloAssoClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HelloAssoClientTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private HelloAssoClient $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new HelloAssoClient(
            'clientId',
            'clientSecret',
            'https://test-api.helloasso.com',
            $this->httpClient,
            $this->logger
        );
    }

    public function testLoginReturnsAccessToken()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'access_token' => 'token123',
            'refresh_token' => 'refresh123',
        ]);
        $this->httpClient->method('request')->willReturn($response);

        $token = $this->service->login();
        $this->assertEquals('token123', $token);
    }
}
