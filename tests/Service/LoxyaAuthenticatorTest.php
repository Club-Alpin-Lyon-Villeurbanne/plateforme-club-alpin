<?php

namespace App\Tests\Service;

use App\Service\LoxyaAuthenticator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class LoxyaAuthenticatorTest extends TestCase
{
    private function makeAuthenticator(MockHttpClient $client, ?LoggerInterface $logger = null): LoxyaAuthenticator
    {
        return new LoxyaAuthenticator(
            'https://materiel.example.com',
            'user@example.com',
            'secret',
            $client,
            $logger ?? $this->createMock(LoggerInterface::class),
        );
    }

    public function testGetTokenReturnsTokenFromSession(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode(['token' => 'jwt-abc']), ['http_code' => 200]),
        ]);

        $this->assertSame('jwt-abc', $this->makeAuthenticator($client)->getToken());
    }

    public function testGetTokenLogsInOnlyOnce(): void
    {
        // Une seule réponse fournie : si un 2e login était déclenché, MockHttpClient lèverait une exception.
        $client = new MockHttpClient([
            new MockResponse(json_encode(['token' => 'jwt-abc']), ['http_code' => 200]),
        ]);
        $authenticator = $this->makeAuthenticator($client);

        $authenticator->getToken();
        $authenticator->getToken();

        $this->assertSame(1, $client->getRequestsCount());
    }

    public function testGetTokenThrowsOnHttpError(): void
    {
        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 401]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Loxya authentication failed (HTTP 401)');

        $this->makeAuthenticator($client)->getToken();
    }

    public function testGetTokenThrowsWhenTokenMissing(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode(['somethingElse' => true]), ['http_code' => 200]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Loxya authentication response missing token');

        $this->makeAuthenticator($client)->getToken();
    }
}
