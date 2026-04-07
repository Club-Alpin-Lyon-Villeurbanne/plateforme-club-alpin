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

class HelloAssoServiceTest extends TestCase
{
    private string $organizationSlug;
    private string $baseUrl;
    private int $activityTypeId;
    private LoggerInterface $logger;
    private UrlGeneratorInterface $urlGenerator;
    private HelloAssoClient $helloAssoClient;
    private HelloAssoService $service;

    /**
     * Initialise les mocks et instancie le service pour chaque test.
     */
    protected function setUp(): void
    {
        $this->organizationSlug = 'test-org';
        $this->baseUrl = 'https://api.helloasso.com';
        $this->activityTypeId = 123;
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->helloAssoClient = $this->createMock(HelloAssoClient::class);

        $this->service = new HelloAssoService(
            $this->organizationSlug,
            $this->baseUrl,
            $this->activityTypeId,
            $this->logger,
            $this->urlGenerator,
            $this->helloAssoClient
        );
    }

    /**
     * Vérifie que isConfigSet retourne true quand tous les paramètres sont renseignés.
     */
    public function testIsConfigSetWhenFullyConfigured(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(true);

        $result = $this->service->isConfigSet();

        $this->assertTrue($result);
    }

    /**
     * Vérifie que isConfigSet retourne false quand les credentials ne sont pas définis.
     */
    public function testIsConfigSetWhenCredentialsNotSet(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(false);

        $result = $this->service->isConfigSet();

        $this->assertFalse($result);
    }

    /**
     * Vérifie que isConfigSet retourne false quand le slug d'organisation est vide.
     */
    public function testIsConfigSetWhenOrganizationSlugEmpty(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(true);

        $service = new HelloAssoService(
            '',
            $this->baseUrl,
            $this->activityTypeId,
            $this->logger,
            $this->urlGenerator,
            $this->helloAssoClient
        );

        $result = $service->isConfigSet();

        $this->assertFalse($result);
    }

    /**
     * Vérifie que isConfigSet retourne false quand l'identifiant de type d'activité est zéro.
     */
    public function testIsConfigSetWhenActivityTypeIdZero(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(true);

        $service = new HelloAssoService(
            $this->organizationSlug,
            $this->baseUrl,
            0,
            $this->logger,
            $this->urlGenerator,
            $this->helloAssoClient
        );

        $result = $service->isConfigSet();

        $this->assertFalse($result);
    }

    /**
     * Vérifie que isConfigSet retourne false quand l'URL de base est vide.
     */
    public function testIsConfigSetWhenBaseUrlEmpty(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(true);

        $service = new HelloAssoService(
            $this->organizationSlug,
            '',
            $this->activityTypeId,
            $this->logger,
            $this->urlGenerator,
            $this->helloAssoClient
        );

        $result = $service->isConfigSet();

        $this->assertFalse($result);
    }

    /**
     * Crée un mock de Evt avec les propriétés nécessaires aux tests.
     */
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

    /**
     * Vérifie que createFormForEvent retourne la réponse de l'API HelloAsso.
     */
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

    /**
     * Vérifie que createFormForEvent transmet les données correctes à l'API.
     */
    public function testCreateFormForEventIncludesCorrectData(): void
    {
        $event = $this->mockEvent('Escalade Adventure', new \DateTimeImmutable('2025-01-15'), 75.50, 456, 'ESCAL456');

        $this->urlGenerator->method('generate')->willReturn('https://example.com/sortie/ESCAL456/456');

        $this->helloAssoClient->expects($this->once())
            ->method('createForm')
            ->willReturn(['id' => 1000]);

        $this->service->createFormForEvent($event);
    }

    /**
     * Vérifie que createFormForEvent fonctionne avec différents montants de paiement.
     */
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

    /**
     * Vérifie que publishFormForEvent appelle l'API quand le slug est valide.
     */
    public function testPublishFormForEventWithValidFormSlug(): void
    {
        $event = $this->createMock(Evt::class);
        $event->method('getHelloAssoFormSlug')->willReturn('test-form-slug');

        $this->helloAssoClient->expects($this->once())
            ->method('publishForm')
            ->with($this->stringContains('test-org'));

        $this->service->publishFormForEvent($event);
    }

    /**
     * Vérifie que publishFormForEvent n'appelle pas l'API si le slug est null.
     */
    public function testPublishFormForEventWithoutFormSlug(): void
    {
        $event = $this->createMock(Evt::class);
        $event->method('getHelloAssoFormSlug')->willReturn(null);

        $this->helloAssoClient->expects($this->never())
            ->method('publishForm');

        $this->service->publishFormForEvent($event);
    }

    /**
     * Vérifie que publishFormForEvent n'appelle pas l'API si le slug est une chaîne vide.
     */
    public function testPublishFormForEventWithEmptyFormSlug(): void
    {
        $event = $this->createMock(Evt::class);
        $event->method('getHelloAssoFormSlug')->willReturn('');

        $this->helloAssoClient->expects($this->never())
            ->method('publishForm');

        $this->service->publishFormForEvent($event);
    }

    /**
     * Vérifie que publishFormForEvent n'appelle pas l'API si le slug d'organisation est vide.
     */
    public function testPublishFormForEventWithEmptyOrganizationSlug(): void
    {
        $service = new HelloAssoService(
            '',
            $this->baseUrl,
            $this->activityTypeId,
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

    /**
     * Vérifie que createFormForEvent fonctionne pour plusieurs dates différentes.
     */
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

    /**
     * Vérifie que isConfigSet retourne false quand la clé API n'est pas définie.
     */
    public function testIsConfigSetWithMissingApiKey(): void
    {
        $this->helloAssoClient->method('areCredentialsSet')->willReturn(false);

        $result = $this->service->isConfigSet();

        $this->assertFalse($result);
    }

    /**
     * Vérifie que le titre de la campagne inclut la date au format [YYYY-MM-DD].
     */
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
