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

        $calls = [];
        $httpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use (&$calls, $createResponse, $groupResponse) {
                $calls[] = ['method' => $method, 'url' => $url, 'json' => $options['json']];

                return str_ends_with($url, '/api/beneficiaries') ? $createResponse : $groupResponse;
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

        $this->assertSame('POST', $calls[0]['method']);
        $this->assertSame('https://materiel.example.com/api/beneficiaries', $calls[0]['url']);
        $this->assertSame('FR', $calls[0]['json']['country'] ?? null);
        $this->assertSame('jean.dupont@example.com', $calls[0]['json']['email']);

        $this->assertSame('PUT', $calls[1]['method']);
        $this->assertSame('https://materiel.example.com/api/users/123', $calls[1]['url']);
        $this->assertSame('readonly-planning-self', $calls[1]['json']['group']);
    }
}
