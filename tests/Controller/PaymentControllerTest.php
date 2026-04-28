<?php

namespace App\Tests\Controller;

use App\Service\HelloAssoClient;
use App\Service\LoxyaReservationService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    private const WEBHOOK_SIGNATURE_KEY = 'test-webhook-secret';
    private const WEBHOOK_SERVER_IP = '127.0.0.1';
    private const LINK_SIGNATURE_KEY = 'test-loxya-link-secret';

    private function makeWebhookRequest($client, string $payload, ?string $ip = null, ?string $signature = null): void
    {
        $client->request('POST', '/webhook/paiement', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HA_SIGNATURE' => $signature ?? hash_hmac('sha256', $payload, self::WEBHOOK_SIGNATURE_KEY),
            'REMOTE_ADDR' => $ip ?? self::WEBHOOK_SERVER_IP,
        ], $payload);
    }

    private static function signLink(int $reservationId, int $amount): string
    {
        return hash_hmac('sha256', $reservationId . '|' . $amount, self::LINK_SIGNATURE_KEY);
    }

    // --- Checkout tests ---

    public function testCheckoutWithoutParamsReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCheckoutWithMissingAmountReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement?reservation_id=42');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCheckoutWithInvalidParamsReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement?reservation_id=0&amount=-100');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCheckoutRedirectsToHelloAsso(): void
    {
        $client = static::createClient();

        $helloAssoClient = $this->createMock(HelloAssoClient::class);
        $helloAssoClient->expects($this->once())
            ->method('createCheckoutIntent')
            ->with(
                $this->isType('string'),
                $this->callback(function (array $params) {
                    return 3500 === $params['totalAmount']
                        && 3500 === $params['initialAmount']
                        && false === $params['containsDonation']
                        && 42 === $params['metadata']['reservation_id']
                        && str_contains($params['itemName'], '42');
                })
            )
            ->willReturn([
                'id' => 123,
                'redirectUrl' => 'https://checkout.helloasso.com/public/gateway/start/abc123',
                'metadata' => ['reservation_id' => 42],
            ]);

        $client->getContainer()->set(HelloAssoClient::class, $helloAssoClient);

        $signature = self::signLink(42, 3500);
        $client->request('GET', '/paiement?reservation_id=42&amount=3500&signature=' . $signature);

        $this->assertResponseRedirects('https://checkout.helloasso.com/public/gateway/start/abc123');
    }

    public function testCheckoutRejectsAmountOverCap(): void
    {
        $client = static::createClient();

        $signature = self::signLink(42, 100001);
        $client->request('GET', '/paiement?reservation_id=42&amount=100001&signature=' . $signature);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCheckoutRendersErrorWhenRedirectUrlMissing(): void
    {
        $client = static::createClient();

        $helloAssoClient = $this->createMock(HelloAssoClient::class);
        $helloAssoClient->method('createCheckoutIntent')->willReturn(['id' => 123]);
        $client->getContainer()->set(HelloAssoClient::class, $helloAssoClient);

        $signature = self::signLink(42, 3500);
        $client->request('GET', '/paiement?reservation_id=42&amount=3500&signature=' . $signature);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Erreur de paiement');
    }

    public function testCheckoutRejectsMissingSignature(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement?reservation_id=42&amount=3500');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCheckoutRejectsInvalidSignature(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement?reservation_id=42&amount=3500&signature=bad-signature');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCheckoutRejectsSignatureWithTamperedAmount(): void
    {
        $client = static::createClient();

        $signature = self::signLink(42, 3500);
        $client->request('GET', '/paiement?reservation_id=42&amount=100&signature=' . $signature);

        $this->assertResponseStatusCodeSame(403);
    }

    // --- Webhook security tests ---

    public function testWebhookRejectsInvalidIp(): void
    {
        $client = static::createClient();
        $payload = json_encode(['eventType' => 'Payment', 'data' => ['id' => 1, 'state' => 'Authorized'], 'metadata' => ['reservation_id' => 1]]);

        $this->makeWebhookRequest($client, $payload, ip: '10.0.0.1');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testWebhookRejectsMissingSignature(): void
    {
        $client = static::createClient();
        $payload = json_encode(['eventType' => 'Payment']);

        $client->request('POST', '/webhook/paiement', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => self::WEBHOOK_SERVER_IP,
        ], $payload);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testWebhookRejectsInvalidSignature(): void
    {
        $client = static::createClient();
        $payload = json_encode(['eventType' => 'Payment']);

        $this->makeWebhookRequest($client, $payload, signature: 'bad-signature');

        $this->assertResponseStatusCodeSame(403);
    }

    // --- Webhook business logic tests ---

    public function testWebhookIgnoresNonPaymentEvent(): void
    {
        $client = static::createClient();

        $payload = json_encode([
            'eventType' => 'Order',
            'data' => ['id' => 1, 'state' => 'Processed'],
            'metadata' => ['reservation_id' => 42],
        ]);

        $this->makeWebhookRequest($client, $payload);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testWebhookIgnoresNonAuthorizedPaymentState(): void
    {
        $client = static::createClient();

        $loxyaService = $this->createMock(LoxyaReservationService::class);
        $loxyaService->expects($this->never())->method('markReservationAsPaid');
        $client->getContainer()->set(LoxyaReservationService::class, $loxyaService);

        $payload = json_encode([
            'eventType' => 'Payment',
            'data' => ['id' => '99999', 'state' => 'Refused'],
            'metadata' => ['reservation_id' => 42],
        ]);

        $this->makeWebhookRequest($client, $payload);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testWebhookProcessesAuthorizedPayment(): void
    {
        $client = static::createClient();

        $loxyaService = $this->createMock(LoxyaReservationService::class);
        $loxyaService->expects($this->once())
            ->method('markReservationAsPaid')
            ->with(42, '99999');
        $client->getContainer()->set(LoxyaReservationService::class, $loxyaService);

        $payload = json_encode([
            'eventType' => 'Payment',
            'data' => ['id' => '99999', 'state' => 'Authorized'],
            'metadata' => ['reservation_id' => 42],
        ]);

        $this->makeWebhookRequest($client, $payload);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testWebhookReturns503OnLoxyaError(): void
    {
        $client = static::createClient();

        $loxyaService = $this->createMock(LoxyaReservationService::class);
        $loxyaService->method('markReservationAsPaid')
            ->willThrowException(new \RuntimeException('Loxya is down'));
        $client->getContainer()->set(LoxyaReservationService::class, $loxyaService);

        $payload = json_encode([
            'eventType' => 'Payment',
            'data' => ['id' => '99999', 'state' => 'Authorized'],
            'metadata' => ['reservation_id' => 42],
        ]);

        $this->makeWebhookRequest($client, $payload);

        $this->assertResponseStatusCodeSame(503);
    }

    // --- Return page tests ---

    public function testReturnPageRendersSuccessWithCode(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement/retour?code=succeeded');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Paiement confirmé');
    }

    public function testReturnPageRendersErrorWithoutCode(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement/retour');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Paiement échoué');
    }

    public function testCancelPageRenders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement/annuler');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Paiement annulé');
    }

    public function testErrorPageRenders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement/erreur');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Erreur de paiement');
    }
}
