<?php

namespace App\Tests\Service;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\User;
use App\Service\HelloAssoClient;
use App\Service\HelloAssoService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloAssoServiceTest extends TestCase
{
    private string $organizationSlug;
    private string $baseUrl;
    private int $activityTypeId;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private UrlGeneratorInterface $urlGenerator;
    private HelloAssoClient $helloAssoClient;
    private HelloAssoService $service;

    protected function setUp(): void
    {
        $this->organizationSlug = 'test-org';
        $this->baseUrl = 'https://api.helloasso.com';
        $this->activityTypeId = 123;
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->helloAssoClient = $this->createMock(HelloAssoClient::class);

        $this->service = new HelloAssoService(
            $this->organizationSlug,
            $this->baseUrl,
            $this->activityTypeId,
            $this->httpClient,
            $this->logger,
            $this->urlGenerator,
            $this->helloAssoClient
        );
    }

    public function testIsConfigSetWhenFullyConfigured(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(true);

        $result = $this->service->isConfigSet();

        $this->assertTrue($result);
    }

    public function testIsConfigSetWhenCredentialsNotSet(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(false);

        $result = $this->service->isConfigSet();

        $this->assertFalse($result);
    }

    public function testIsConfigSetWhenOrganizationSlugEmpty(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(true);

        $service = new HelloAssoService(
            '',
            $this->baseUrl,
            $this->activityTypeId,
            $this->httpClient,
            $this->logger,
            $this->urlGenerator,
            $this->helloAssoClient
        );

        $result = $service->isConfigSet();

        $this->assertFalse($result);
    }

    public function testIsConfigSetWhenActivityTypeIdZero(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(true);

        $service = new HelloAssoService(
            $this->organizationSlug,
            $this->baseUrl,
            0,
            $this->httpClient,
            $this->logger,
            $this->urlGenerator,
            $this->helloAssoClient
        );

        $result = $service->isConfigSet();

        $this->assertFalse($result);
    }

    public function testIsConfigSetWhenBaseUrlEmpty(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(true);

        $service = new HelloAssoService(
            $this->organizationSlug,
            '',
            $this->activityTypeId,
            $this->httpClient,
            $this->logger,
            $this->urlGenerator,
            $this->helloAssoClient
        );

        $result = $service->isConfigSet();

        $this->assertFalse($result);
    }

    private function mockEvent(string $titre, \DateTimeImmutable $date, float $amount, int $id, string $code, string $commissionTitle = 'Alpinisme', string $lastname = 'Dupont', string $place = 'Chamonix'): Evt
    {
        $commission = $this->createMock(Commission::class);
        $commission->method('getTitle')->willReturn($commissionTitle);

        $user = $this->createMock(User::class);
        $user->method('getLastname')->willReturn($lastname);

        $event = $this->createMock(Evt::class);
        $event->method('getTitre')->willReturn($titre);
        $event->method('getStartDate')->willReturn($date);
        $event->method('getPaymentAmount')->willReturn($amount);
        $event->method('getId')->willReturn($id);
        $event->method('getCode')->willReturn($code);
        $event->method('getCommission')->willReturn($commission);
        $event->method('getUser')->willReturn($user);
        $event->method('getPlace')->willReturn($place);

        return $event;
    }

    public function testCreateFormForEvent(): void
    {
        $event = $this->mockEvent('Test Event', new \DateTimeImmutable('2024-12-20'), 50.00, 123, 'TEST123');

        $this->urlGenerator->method('generate')
            ->with('sortie', ['code' => 'TEST123', 'id' => 123], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/sortie/TEST123/123');

        $expectedResponse = [
            'id' => 999,
            'slug' => 'test-form',
            'publicUrl' => 'https://helloasso.com/forms/test-form',
        ];

        $this->helloAssoClient->expects($this->once())
            ->method('createForm')
            ->willReturn($expectedResponse);

        $result = $this->service->createFormForEvent($event);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testCreateFormForEventIncludesCorrectData(): void
    {
        $event = $this->mockEvent('Escalade Adventure', new \DateTimeImmutable('2025-01-15'), 75.50, 456, 'ESCAL456');

        $this->urlGenerator->method('generate')->willReturn('https://example.com/sortie/ESCAL456/456');

        $this->helloAssoClient->expects($this->once())
            ->method('createForm')
            ->willReturn(['id' => 1000]);

        $this->service->createFormForEvent($event);
    }

    public function testCreateFormForEventWithDifferentPaymentAmounts(): void
    {
        $paymentAmounts = [10.00, 50.50, 100.99];

        $this->helloAssoClient->expects($this->exactly(3))
            ->method('createForm')
            ->willReturn(['id' => 1001]);

        foreach ($paymentAmounts as $amount) {
            $event = $this->mockEvent('Event', new \DateTimeImmutable('2024-12-20'), $amount, 789, 'CODE789');

            $this->urlGenerator->method('generate')->willReturn('https://example.com/sortie/CODE789/789');

            $this->service->createFormForEvent($event);
        }
    }

    public function testPublishFormForEventWithValidFormSlug(): void
    {
        $event = $this->createMock(Evt::class);
        $event->method('getHelloAssoFormSlug')->willReturn('test-form-slug');

        $this->helloAssoClient->expects($this->once())
            ->method('publishForm')
            ->with($this->stringContains('test-org'));

        $this->service->publishFormForEvent($event);
    }

    public function testPublishFormForEventWithoutFormSlug(): void
    {
        $event = $this->createMock(Evt::class);
        $event->method('getHelloAssoFormSlug')->willReturn(null);

        $this->helloAssoClient->expects($this->never())
            ->method('publishForm');

        $this->service->publishFormForEvent($event);
    }

    public function testPublishFormForEventWithEmptyFormSlug(): void
    {
        $event = $this->createMock(Evt::class);
        $event->method('getHelloAssoFormSlug')->willReturn('');

        $this->helloAssoClient->expects($this->never())
            ->method('publishForm');

        $this->service->publishFormForEvent($event);
    }

    public function testPublishFormForEventWithEmptyOrganizationSlug(): void
    {
        $service = new HelloAssoService(
            '',
            $this->baseUrl,
            $this->activityTypeId,
            $this->httpClient,
            $this->logger,
            $this->urlGenerator,
            $this->helloAssoClient
        );

        $event = $this->createMock(Evt::class);
        $event->method('getHelloAssoFormSlug')->willReturn('test-form-slug');

        $this->helloAssoClient->expects($this->never())
            ->method('publishForm');

        $service->publishFormForEvent($event);
    }

    public function testCreateFormForEventWithMultipleDates(): void
    {
        $dates = [
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-06-15'),
            new \DateTimeImmutable('2024-12-31'),
        ];

        $this->helloAssoClient->expects($this->exactly(3))
            ->method('createForm')
            ->willReturn(['id' => 2000]);

        foreach ($dates as $date) {
            $event = $this->mockEvent('Event', $date, 50.00, 111, 'CODE111');

            $this->urlGenerator->method('generate')->willReturn('https://example.com/sortie');

            $this->service->createFormForEvent($event);
        }
    }

    public function testIsConfigSetWithMissingApiKey(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(false);

        $result = $this->service->isConfigSet();

        $this->assertFalse($result);
    }

    public function testCreateFormForEventBuildsTitleWithDate(): void
    {
        $event = $this->mockEvent('Noël Event', new \DateTimeImmutable('2024-12-25'), 30.00, 999, 'NOEL999');

        $this->urlGenerator->method('generate')->willReturn('https://example.com/sortie');

        $this->helloAssoClient->expects($this->once())
            ->method('createForm')
            ->willReturn(['id' => 3000]);

        $this->service->createFormForEvent($event);
    }
}
