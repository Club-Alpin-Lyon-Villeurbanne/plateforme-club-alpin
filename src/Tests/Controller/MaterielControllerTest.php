<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Service\MaterielApiService;
use App\Service\MaterielEmailService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MaterielControllerTest extends TestCase
{
    private MaterielController $controller;
    private MaterielApiService $materielApiService;
    private MaterielEmailService $materielEmailService;
    private LoggerInterface $logger;
    private TokenStorageInterface $tokenStorage;
    private Session $session;
    private User $user;

    protected function setUp(): void
    {
        $this->materielApiService = $this->createMock(MaterielApiService::class);
        $this->materielEmailService = $this->createMock(MaterielEmailService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->session = $this->createMock(Session::class);

        $this->controller = new MaterielController(
            $this->materielApiService,
            $this->materielEmailService,
            $this->logger
        );

        // Mock user
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setFirstname('John');
        $this->user->setLastname('Doe');

        // Mock token
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        // Mock session
        $flashBag = $this->createMock(FlashBagInterface::class);
        $this->session->method('getFlashBag')->willReturn($flashBag);
    }

    public function testIndex(): void
    {
        $result = $this->controller->index();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('materiel_platform_url', $result);
        $this->assertEquals($this->user, $result['user']);
    }

    public function testCreateAccountSuccess(): void
    {
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $this->materielApiService->expects($this->once())
            ->method('userExists')
            ->with($this->user)
            ->willReturn(false);

        $this->materielApiService->expects($this->once())
            ->method('createUser')
            ->with($this->user)
            ->willReturn([
                'email' => 'test@example.com',
                'password' => 'password123',
                'pseudo' => 'J.DOE'
            ]);

        $this->materielEmailService->expects($this->once())
            ->method('sendAccountCreationEmail')
            ->with(
                'test@example.com',
                'John',
                'Doe',
                [
                    'email' => 'test@example.com',
                    'password' => 'password123',
                    'pseudo' => 'J.DOE'
                ]
            );

        $response = $this->controller->createAccount($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('compte a été créé avec succès', $data['message']);
    }

    public function testCreateAccountUserAlreadyExists(): void
    {
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $this->materielApiService->expects($this->once())
            ->method('userExists')
            ->with($this->user)
            ->willReturn(true);

        $response = $this->controller->createAccount($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('déjà un compte', $data['message']);
    }

    public function testCreateAccountError(): void
    {
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $this->materielApiService->expects($this->once())
            ->method('userExists')
            ->with($this->user)
            ->willReturn(false);

        $this->materielApiService->expects($this->once())
            ->method('createUser')
            ->with($this->user)
            ->willThrowException(new \RuntimeException('API Error'));

        $response = $this->controller->createAccount($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('erreur est survenue', $data['message']);
    }
} 