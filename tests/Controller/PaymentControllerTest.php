<?php

namespace App\Tests\Controller;

use App\Messenger\Message\ReservationPaid;
use App\Service\HelloAssoClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    private const WEBHOOK_SIGNATURE_KEY = 'test-webhook-secret';
    private const WEBHOOK_SERVER_IP = '127.0.0.1';

    private function makeWebhookRequest($client, string $payload, ?string $ip = null, ?string $signature = null): void
    {
        $client->request('POST', '/webhook/paiement', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HA_SIGNATURE' => $signature ?? hash_hmac('sha256', $payload, self::WEBHOOK_SIGNATURE_KEY),
            'REMOTE_ADDR' => $ip ?? self::WEBHOOK_SERVER_IP,
        ], $payload);
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
            ->willReturn([
                'id' => 123,
                'redirectUrl' => 'https://checkout.helloasso.com/public/gateway/start/abc123',
                'metadata' => ['reservation_id' => 42],
            ]);

        $client->getContainer()->set(HelloAssoClient::class, $helloAssoClient);

        $client->request('GET', '/paiement?reservation_id=42&amount=3500');

        $this->assertResponseRedirects('https://checkout.helloasso.com/public/gateway/start/abc123');
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

    public function testWebhookProcessesAuthorizedPayment(): void
    {
        $client = static::createClient();

        $payload = json_encode([
            'eventType' => 'Payment',
            'data' => ['id' => '99999', 'state' => 'Authorized'],
            'metadata' => ['reservation_id' => 42],
        ]);

        $this->makeWebhookRequest($client, $payload);

        $this->assertResponseStatusCodeSame(200);

        $transport = $client->getContainer()->get('messenger.transport.alertes');
        $messages = $transport->get();
        $this->assertCount(1, $messages);

        $message = $messages[0]->getMessage();
        $this->assertInstanceOf(ReservationPaid::class, $message);
        $this->assertEquals(42, $message->reservationId);
        $this->assertEquals('99999', $message->helloAssoPaymentId);
    }

    // --- Return page tests ---

    public function testReturnPageRendersSuccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement/retour');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Paiement pris en compte');
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
