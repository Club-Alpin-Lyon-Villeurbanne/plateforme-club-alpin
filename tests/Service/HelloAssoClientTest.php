<?php

namespace App\Tests\Service;

use App\Service\HelloAssoClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HelloAssoClientTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private CacheInterface $cache;
    private HelloAssoClient $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->service = new HelloAssoClient(
            'clientId',
            'clientSecret',
            'https://test-api.helloasso.com',
            $this->httpClient,
            $this->logger,
            $this->cache,
        );
    }

    private function mockCachePassthrough(): void
    {
        $this->cache->method('get')->willReturnCallback(
            fn (string $key, callable $callback) => $callback($this->createMock(ItemInterface::class))
        );
    }

    public function testLoginReturnsAccessToken(): void
    {
        $this->mockCachePassthrough();

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'access_token' => 'token123',
            'expires_in' => 1800,
        ]);
        $this->httpClient->method('request')->willReturn($response);

        $token = $this->service->login();
        $this->assertEquals('token123', $token);
    }

    public function testLoginUsesCache(): void
    {
        $this->cache->method('get')->willReturn('cached_token');

        $this->httpClient->expects($this->never())->method('request');

        $token = $this->service->login();
        $this->assertEquals('cached_token', $token);
    }

    public function testCreateFormThrowsOnError(): void
    {
        $this->mockCachePassthrough();

        $loginResponse = $this->createMock(ResponseInterface::class);
        $loginResponse->method('toArray')->willReturn([
            'access_token' => 'token123',
            'expires_in' => 1800,
        ]);

        $formResponse = $this->createMock(ResponseInterface::class);
        $formResponse->method('getStatusCode')->willReturn(400);
        $formResponse->method('getContent')->willReturn('Bad Request');

        $this->httpClient->method('request')->willReturnOnConsecutiveCalls($loginResponse, $formResponse);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HelloAsso createForm failed');

        $this->service->createForm('/v5/test-endpoint', ['key' => 'value']);
    }

    public function testPublishFormThrowsOnError(): void
    {
        $this->mockCachePassthrough();

        $loginResponse = $this->createMock(ResponseInterface::class);
        $loginResponse->method('toArray')->willReturn([
            'access_token' => 'token123',
            'expires_in' => 1800,
        ]);

        $publishResponse = $this->createMock(ResponseInterface::class);
        $publishResponse->method('getStatusCode')->willReturn(500);
        $publishResponse->method('getContent')->willReturn('Internal Server Error');

        $this->httpClient->method('request')->willReturnOnConsecutiveCalls($loginResponse, $publishResponse);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HelloAsso publishForm failed');

        $this->service->publishForm('/v5/test-endpoint');
    }

    public function testCreateCheckoutIntentReturnsRedirectUrl(): void
    {
        $this->mockCachePassthrough();

        $loginResponse = $this->createMock(ResponseInterface::class);
        $loginResponse->method('toArray')->willReturn([
            'access_token' => 'token123',
            'expires_in' => 1800,
        ]);

        $checkoutResponse = $this->createMock(ResponseInterface::class);
        $checkoutResponse->method('getStatusCode')->willReturn(200);
        $checkoutResponse->method('toArray')->willReturn([
            'id' => 42,
            'redirectUrl' => 'https://checkout.helloasso.com/public/gateway/start/abc123',
            'metadata' => ['reservation_id' => 1],
        ]);

        $this->httpClient->method('request')->willReturnOnConsecutiveCalls($loginResponse, $checkoutResponse);

        $result = $this->service->createCheckoutIntent('my-org', [
            'totalAmount' => 3500,
            'initialAmount' => 3500,
            'itemName' => 'Test',
            'containsDonation' => false,
        ]);

        $this->assertEquals('https://checkout.helloasso.com/public/gateway/start/abc123', $result['redirectUrl']);
        $this->assertEquals(42, $result['id']);
    }

    public function testCreateCheckoutIntentThrowsOnError(): void
    {
        $this->mockCachePassthrough();

        $loginResponse = $this->createMock(ResponseInterface::class);
        $loginResponse->method('toArray')->willReturn([
            'access_token' => 'token123',
            'expires_in' => 1800,
        ]);

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(422);
        $errorResponse->method('getContent')->willReturn('Validation error');

        $this->httpClient->method('request')->willReturnOnConsecutiveCalls($loginResponse, $errorResponse);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HelloAsso checkout intent failed');

        $this->service->createCheckoutIntent('my-org', ['totalAmount' => -1]);
    }
}
