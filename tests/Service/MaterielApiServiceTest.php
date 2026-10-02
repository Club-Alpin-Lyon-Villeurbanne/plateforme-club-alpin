<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\LoxyaAuthenticator;
use App\Service\MaterielApiService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MaterielApiServiceTest extends TestCase
{
    private const API_URL = 'https://materiel.example.com';

    private function makeService(HttpClientInterface $client, ?EntityManagerInterface $entityManager = null): MaterielApiService
    {
        $authenticator = $this->createMock(LoxyaAuthenticator::class);
        $authenticator->method('getToken')->willReturn('session-jwt-token');

        return new MaterielApiService(
            $this->createMock(LoggerInterface::class),
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $authenticator,
            $client,
            self::API_URL,
        );
    }

    private function makeUser(string $firstname = 'Jean', string $lastname = 'DUPONT'): User
    {
        return (new User())
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail('jean.dupont@example.com');
    }

    private function makeResponse(int $statusCode, array $decoded = [], string $raw = ''): MockObject&ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('toArray')->willReturn($decoded);
        $response->method('getContent')->willReturn($raw);

        return $response;
    }

    /**
     * @param array<int, array{method: string, url: string, json: array<string, mixed>}> $calls
     */
    private function recordingClient(array &$calls, ResponseInterface $onCreate, ResponseInterface $onGroupUpdate): MockObject&HttpClientInterface
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use (&$calls, $onCreate, $onGroupUpdate) {
                $calls[] = ['method' => $method, 'url' => $url, 'json' => $options['json'] ?? []];

                return str_ends_with($url, '/api/beneficiaries') ? $onCreate : $onGroupUpdate;
            });

        return $client;
    }

    public function testCreateUserSendsCountryRequiredByLoxya(): void
    {
        $calls = [];
        $client = $this->recordingClient($calls, $this->makeResponse(201, ['id' => 123]), $this->makeResponse(200));

        $this->makeService($client)->createUser($this->makeUser());

        $this->assertSame('POST', $calls[0]['method']);
        $this->assertSame(self::API_URL . '/api/beneficiaries', $calls[0]['url']);
        $this->assertSame('FR', $calls[0]['json']['country'] ?? null);
        $this->assertSame('jean.dupont@example.com', $calls[0]['json']['email']);
        $this->assertTrue($calls[0]['json']['can_make_reservation']);

        $this->assertSame('PUT', $calls[1]['method']);
        $this->assertSame(self::API_URL . '/api/users/123', $calls[1]['url']);
        $this->assertSame('readonly-planning-self', $calls[1]['json']['group']);
    }

    public function testCreateUserReturnsCredentialsAndRecordsCreationDate(): void
    {
        $calls = [];
        $client = $this->recordingClient($calls, $this->makeResponse(201, ['id' => 123]), $this->makeResponse(200));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $user = $this->makeUser();
        $credentials = $this->makeService($client, $entityManager)->createUser($user);

        $this->assertSame('jean.dupont@example.com', $credentials['email']);
        $this->assertSame('jeandupont', $credentials['pseudo']);
        $this->assertSame($calls[0]['json']['password'], $credentials['password']);
        $this->assertNotNull($user->getMaterielAccountCreatedAt());
    }

    public function testCreateUserThrowsAndRecordsNothingWhenLoxyaRejectsThePayload(): void
    {
        $rejection = '{"success":false,"error":{"code":400,"message":"Validation failed."}}';
        $calls = [];
        $client = $this->recordingClient($calls, $this->makeResponse(400, [], $rejection), $this->makeResponse(200));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $user = $this->makeUser();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validation failed.');

        try {
            $this->makeService($client, $entityManager)->createUser($user);
        } finally {
            $this->assertNull($user->getMaterielAccountCreatedAt());
        }
    }

    public function testCreateUserReturnsCredentialsEvenIfPlanningGroupCannotBeAssigned(): void
    {
        $calls = [];
        $client = $this->recordingClient($calls, $this->makeResponse(201, ['id' => 123]), $this->makeResponse(500, [], 'Internal error'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $user = $this->makeUser();
        $credentials = $this->makeService($client, $entityManager)->createUser($user);

        $this->assertSame('jeandupont', $credentials['pseudo']);
        $this->assertNotNull($user->getMaterielAccountCreatedAt());
    }

    public function testCreateUserRecordsTheAccountBeforeAssigningPlanningGroup(): void
    {
        $sequence = [];

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')
            ->willReturnCallback(function (string $method, string $url) use (&$sequence) {
                $sequence[] = $method . ' ' . parse_url($url, \PHP_URL_PATH);

                return str_ends_with($url, '/api/beneficiaries')
                    ? $this->makeResponse(201, ['id' => 123])
                    : $this->makeResponse(200);
            });

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('flush')->willReturnCallback(function () use (&$sequence) {
            $sequence[] = 'flush';
        });

        $this->makeService($client, $entityManager)->createUser($this->makeUser());

        $this->assertSame(
            ['POST /api/beneficiaries', 'flush', 'PUT /api/users/123'],
            $sequence
        );
    }

    /**
     * @dataProvider provideNamesAndPseudos
     */
    public function testPseudoKeepsOnlyUnaccentedLetters(string $firstname, string $lastname, string $expected): void
    {
        $calls = [];
        $client = $this->recordingClient($calls, $this->makeResponse(201, ['id' => 123]), $this->makeResponse(200));

        $this->makeService($client)->createUser($this->makeUser($firstname, $lastname));

        $this->assertSame($expected, $calls[0]['json']['pseudo']);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideNamesAndPseudos(): iterable
    {
        yield 'nom simple' => ['Jean', 'DUPONT', 'jeandupont'];
        yield 'prénom composé' => ['Jean-Luc', 'MARTIN', 'jeanlucmartin'];
        yield 'nom à particule' => ['Marie', 'DE LA TOUR', 'mariedelatour'];
        yield 'accents' => ['Éric', 'BÉCHARD', 'ricbchard'];
    }

    public function testGeneratedPasswordIsTwelveCharacters(): void
    {
        $calls = [];
        $client = $this->recordingClient($calls, $this->makeResponse(201, ['id' => 123]), $this->makeResponse(200));

        $this->makeService($client)->createUser($this->makeUser());

        $this->assertSame(12, \strlen($calls[0]['json']['password']));
    }

    public function testUserExistsOnLoxyaSearchesByEmail(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->stringContains('search[]=' . urlencode('jean.dupont@example.com')),
                $this->callback(fn (array $options) => 'Bearer session-jwt-token' === $options['headers']['Authorization'])
            )
            ->willReturn($this->makeResponse(200, ['data' => [['id' => 5]]]));

        $this->assertTrue($this->makeService($client)->userExistsOnLoxya($this->makeUser()));
    }

    public function testUserExistsOnLoxyaIsFalseWhenNoMatch(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($this->makeResponse(200, ['data' => []]));

        $this->assertFalse($this->makeService($client)->userExistsOnLoxya($this->makeUser()));
    }

    public function testUserExistsOnLoxyaIsFalseWhenApiFails(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($this->makeResponse(503, [], 'Service Unavailable'));

        $this->assertFalse($this->makeService($client)->userExistsOnLoxya($this->makeUser()));
    }

    public function testUserExistsOnLoxyaIsFalseWhenRequestThrows(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(new \RuntimeException('Connection timed out'));

        $this->assertFalse($this->makeService($client)->userExistsOnLoxya($this->makeUser()));
    }
}
