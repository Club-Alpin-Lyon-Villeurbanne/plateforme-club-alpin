<?php

namespace App\Tests\Service;

use App\Entity\Config;
use App\Entity\Evt;
use App\Repository\ConfigRepository;
use App\Service\HelloAssoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HelloAssoServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private RouterInterface $router;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ConfigRepository $configRepository;
    private HelloAssoService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configRepository = $this->createMock(ConfigRepository::class);

        $this->service = new HelloAssoService(
            'clientId',
            'clientSecret',
            'orgSlug',
            'https://test-api.helloasso.com',
            1,
            $this->configRepository,
            $this->httpClient,
            $this->router,
            $this->entityManager,
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
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $this->httpClient->method('request')->willReturn($response);

        $this->configRepository->expects($this->exactly(2))
            ->method('saveConfigValue')
            ->withConsecutive(
                ['organization_refresh_token', 'refresh123'],
                ['organization_token_get_date', $now]
            )
        ;

        $token = $this->service->login();
        $this->assertEquals('token123', $token);
    }

    public function testGetAccessTokenFromRefreshTokenReturnsToken()
    {
        $date = (new \DateTime());
        $date->add(\DateInterval::createFromDateString('-15 days'));

        $config = $this->createMock(Config::class);
        $config->expects($this->any())
            ->method('getValue')
            ->willReturnOnConsecutiveCalls('refresh123', $date->format('Y-m-d H:i:s'))
        ;
        $repo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findOneBy'])
            ->getMock()
        ;
        $repo->method('findOneBy')->willReturn($config);

        $this->entityManager->method('getRepository')->willReturn($repo);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'access_token' => 'token456',
            'refresh_token' => 'refresh456',
        ]);
        $this->httpClient->method('request')->willReturn($response);

        $token = $this->service->getAccessTokenFromRefreshToken();
        $this->assertEquals('token456', $token);
    }

    public function testGetPaymentsForEventReturnsPayers()
    {
        $evt = $this->createMock(Evt::class);
        $evt->method('getHelloAssoFormSlug')->willReturn('formSlug');

        $this->service = $this->getMockBuilder(HelloAssoService::class)
            ->setConstructorArgs([
                'clientId',
                'clientSecret',
                'orgSlug',
                'https://api.helloasso.com',
                1,
                $this->configRepository,
                $this->httpClient,
                $this->router,
                $this->entityManager,
                $this->logger,
            ])
            ->onlyMethods(['getAccessTokenFromRefreshToken'])
            ->getMock()
        ;

        $this->service->method('getAccessTokenFromRefreshToken')->willReturn('token789');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'data' => [
                [
                    'state' => 'Authorized',
                    'payer' => ['email' => 'payer1@example.com'],
                ],
                [
                    'state' => 'Pending',
                    'payer' => ['email' => 'payer2@example.com'],
                ],
            ],
        ]);
        $this->httpClient->method('request')->willReturn($response);

        $payers = $this->service->getPaymentsForEvent($evt);
        $this->assertEquals(['payer1@example.com'], $payers);
    }

    public function testGetPaymentsForEventReturnsEmptyArrayIfNoAuthorized()
    {
        $evt = $this->createMock(Evt::class);
        $evt->method('getHelloAssoFormSlug')->willReturn('formSlug');

        $this->service = $this->getMockBuilder(HelloAssoService::class)
            ->setConstructorArgs([
                'clientId',
                'clientSecret',
                'orgSlug',
                'https://api.helloasso.com',
                1,
                $this->configRepository,
                $this->httpClient,
                $this->router,
                $this->entityManager,
                $this->logger,
            ])
            ->onlyMethods(['getAccessTokenFromRefreshToken'])
            ->getMock()
        ;

        $this->service->method('getAccessTokenFromRefreshToken')->willReturn('token789');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'data' => [
                [
                    'state' => 'Pending',
                    'payer' => ['email' => 'payer2@example.com'],
                ],
            ],
        ]);
        $this->httpClient->method('request')->willReturn($response);

        $payers = $this->service->getPaymentsForEvent($evt);
        $this->assertEquals([], $payers);
    }
}
