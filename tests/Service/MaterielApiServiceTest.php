<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\LoxyaAuthenticator;
use App\Service\MaterielApiService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MaterielApiServiceTest extends TestCase
{
    public function testCreateUserSendsCountryRequiredByLoxya(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $authenticator = $this->createMock(LoxyaAuthenticator::class);
        $authenticator->method('getToken')->willReturn('session-jwt-token');

        $createResponse = $this->createMock(ResponseInterface::class);
        $createResponse->method('getStatusCode')->willReturn(201);
        $createResponse->method('toArray')->willReturn(['id' => 123]);

        $groupResponse = $this->createMock(ResponseInterface::class);
        $groupResponse->method('getStatusCode')->willReturn(200);

        $capturedPayload = null;
        $httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use (&$capturedPayload, $createResponse, $groupResponse) {
                if (str_ends_with($url, '/api/beneficiaries')) {
                    $capturedPayload = $options['json'];

                    return $createResponse;
                }

                return $groupResponse;
            });

        $user = (new User())
            ->setFirstname('Jean')
            ->setLastname('DUPONT')
            ->setEmail('jean.dupont@example.com');

        $service = new MaterielApiService(
            $this->createMock(LoggerInterface::class),
            $this->createMock(EntityManagerInterface::class),
            $authenticator,
            $httpClient,
            'https://materiel.example.com',
        );

        $service->createUser($user);

        // Loxya rejette la création (HTTP 400) si le pays est absent depuis le 01/09/2026.
        $this->assertSame('FR', $capturedPayload['country'] ?? null);
    }
}
