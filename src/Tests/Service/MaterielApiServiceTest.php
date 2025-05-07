<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\MaterielApiService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

class MaterielApiServiceTest extends TestCase
{
    private MaterielApiService $service;
    private MockHttpClient $httpClient;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private string $apiBaseUrl = 'https://api.example.com';
    private string $apiUsername = 'test@example.com';
    private string $apiPassword = 'password123';

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->service = new MaterielApiService(
            $this->logger,
            $this->entityManager,
            $this->apiBaseUrl,
            $this->apiUsername,
            $this->apiPassword
        );
    }

    public function testAuthenticateSuccess(): void
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['token' => 'test-token']), [
                'http_code' => Response::HTTP_OK,
            ]),
        ]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Authentification à l\'API Loxya', ['url' => $this->apiBaseUrl . '/api/session']);

        $this->service->authenticate();
        $this->assertEquals('test-token', $this->service->getJwtToken());
    }

    public function testAuthenticateFailure(): void
    {
        $this->httpClient->setResponseFactory([
            new MockResponse('Invalid credentials', [
                'http_code' => Response::HTTP_UNAUTHORIZED,
            ]),
        ]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Échec de l\'authentification', [
                'statusCode' => Response::HTTP_UNAUTHORIZED,
                'response' => 'Invalid credentials',
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to authenticate with Loxya API: Invalid response code 401');

        $this->service->authenticate();
    }

    public function testCreateUserSuccess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $this->httpClient->setResponseFactory([
            // Response for authentication
            new MockResponse(json_encode(['token' => 'test-token']), [
                'http_code' => Response::HTTP_OK,
            ]),
            // Response for user creation
            new MockResponse(json_encode(['id' => 1]), [
                'http_code' => Response::HTTP_CREATED,
            ]),
        ]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->service->createUser($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('password', $result);
        $this->assertArrayHasKey('pseudo', $result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('J.DOE', $result['pseudo']);
        $this->assertNotNull($user->getMaterielAccountCreatedAt());
    }

    public function testCreateUserFailure(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');

        $this->httpClient->setResponseFactory([
            // Response for authentication
            new MockResponse(json_encode(['token' => 'test-token']), [
                'http_code' => Response::HTTP_OK,
            ]),
            // Response for user creation
            new MockResponse('User already exists', [
                'http_code' => Response::HTTP_CONFLICT,
            ]),
        ]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Échec de la création de l\'utilisateur', [
                'statusCode' => Response::HTTP_CONFLICT,
                'response' => 'User already exists',
            ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create user: User already exists');

        $this->service->createUser($user);
    }

    public function testUserExists(): void
    {
        $user = new User();

        // Test when user has no account
        $this->assertFalse($this->service->userExists($user));

        // Test when user has an account
        $user->setMaterielAccountCreatedAt(new \DateTime());
        $this->assertTrue($this->service->userExists($user));
    }

    public function testGeneratePassword(): void
    {
        $password = $this->service->generatePassword();

        $this->assertIsString($password);
        $this->assertEquals(12, \strlen($password));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9!@#$%^&*()_+]{12}$/', $password);
    }

    public function testGeneratePseudo(): void
    {
        $pseudo = $this->service->generatePseudo('John', 'Doe');

        $this->assertEquals('J.DOE', $pseudo);
    }
}
