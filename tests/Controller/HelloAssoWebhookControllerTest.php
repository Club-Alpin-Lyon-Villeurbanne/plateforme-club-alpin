<?php

namespace App\Tests\Controller;

use App\Service\LoxyaReservationService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HelloAssoWebhookControllerTest extends WebTestCase
{
    private const SERVER_IP = '127.0.0.1';

    private function postNotification($client, string $payload, string $ip = self::SERVER_IP): void
    {
        $client->request('POST', '/webhook/notification', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => $ip,
        ], $payload);
    }

    public function testMaterialPaymentMarksReservationAsPaid(): void
    {
        // Pas de header de signature : HelloAsso n'en envoie pas (compte non-partenaire).
        // L'IP valide suffit, et la metadata reservation_id déclenche le bridge Loxya.
        $client = static::createClient();

        $loxya = $this->createMock(LoxyaReservationService::class);
        $loxya->expects($this->once())->method('markReservationAsPaid')->with(616, 'ha-pay-1');
        $client->getContainer()->set(LoxyaReservationService::class, $loxya);

        $this->postNotification($client, json_encode([
            'eventType' => 'Payment',
            'data' => ['id' => 'ha-pay-1', 'state' => 'Authorized'],
            'metadata' => ['reservation_id' => 616],
        ]));

        $this->assertResponseStatusCodeSame(200);
    }

    public function testMaterialPaymentReturns503OnLoxyaError(): void
    {
        $client = static::createClient();

        $loxya = $this->createMock(LoxyaReservationService::class);
        $loxya->method('markReservationAsPaid')->willThrowException(new \RuntimeException('Loxya down'));
        $client->getContainer()->set(LoxyaReservationService::class, $loxya);

        $this->postNotification($client, json_encode([
            'eventType' => 'Payment',
            'data' => ['id' => 'ha-pay-1', 'state' => 'Authorized'],
            'metadata' => ['reservation_id' => 616],
        ]));

        $this->assertResponseStatusCodeSame(503);
    }

    public function testMaterialPaymentIgnoresNonAuthorizedState(): void
    {
        $client = static::createClient();

        $loxya = $this->createMock(LoxyaReservationService::class);
        $loxya->expects($this->never())->method('markReservationAsPaid');
        $client->getContainer()->set(LoxyaReservationService::class, $loxya);

        $this->postNotification($client, json_encode([
            'eventType' => 'Payment',
            'data' => ['id' => 'ha-pay-1', 'state' => 'Refused'],
            'metadata' => ['reservation_id' => 616],
        ]));

        $this->assertResponseStatusCodeSame(200);
    }

    public function testRejectsInvalidIp(): void
    {
        $client = static::createClient();

        $this->postNotification($client, json_encode([
            'eventType' => 'Payment',
            'data' => ['id' => 'ha-pay-1', 'state' => 'Authorized'],
            'metadata' => ['reservation_id' => 616],
        ]), ip: '10.0.0.1');

        $this->assertResponseStatusCodeSame(400);
    }
}
