<?php

namespace App\Tests\Controller;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Service\LoxyaReservationService;
use App\Tests\WebTestCase;

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
        $client = $this->client;

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
        $client = $this->client;

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
        $client = $this->client;

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
        $client = $this->client;

        $this->postNotification($client, json_encode([
            'eventType' => 'Payment',
            'data' => ['id' => 'ha-pay-1', 'state' => 'Authorized'],
            'metadata' => ['reservation_id' => 616],
        ]), ip: '10.0.0.1');

        $this->assertResponseStatusCodeSame(400);
    }

    private function createEventWithPayment(): Evt
    {
        $event = $this->createEvent($this->signup());
        $event->setHelloAssoFormSlug('sortie-' . bin2hex(random_bytes(6)));
        $this->getContainer()->get('doctrine')->getManager()->flush();

        return $event;
    }

    private function signupNamed(string $firstname, string $lastname): User
    {
        $user = $this->signup()->setFirstname($firstname)->setLastname($lastname);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        return $user;
    }

    private function postRegistrationPayment(Evt $event, array $payer): void
    {
        $this->postNotification($this->client, json_encode([
            'eventType' => 'Payment',
            'data' => [
                'payer' => $payer,
                'items' => [['type' => 'Registration', 'state' => 'Processed']],
                'order' => ['formSlug' => $event->getHelloAssoFormSlug()],
            ],
        ]));
    }

    private function reload(Evt $event): Evt
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em->find(Evt::class, $event->getId());
    }

    public function testPaiementRapprocheParNomQuandEmailInconnu(): void
    {
        $event = $this->createEventWithPayment();
        $participant = $this->signupNamed('Élodie', 'DUPRé');
        $event->addParticipation($participant, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $this->postRegistrationPayment($event, ['email' => 'inconnu-' . bin2hex(random_bytes(6)) . '@example.org', 'firstName' => 'elodie', 'lastName' => 'Dupré']);

        $this->assertResponseStatusCodeSame(200);
        $event = $this->reload($event);
        $participant = $this->getContainer()->get('doctrine')->getManager()->find(User::class, $participant->getId());
        $this->assertTrue($event->getParticipation($participant)->hasPaid());
        $this->assertCount(0, $event->getUnrecognizedPayers());
    }

    public function testPayeurAvecCompteMaisNonInscritEstEnregistreCommePayeurNonReconnu(): void
    {
        $event = $this->createEventWithPayment();
        $payeur = $this->signupNamed('Paul', 'Durand');

        $this->postRegistrationPayment($event, ['email' => $payeur->getEmail(), 'firstName' => 'Paul', 'lastName' => 'Durand']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(1, $this->reload($event)->getUnrecognizedPayers());
    }

    public function testPayeurSansNomEstEnregistreSansErreur(): void
    {
        $event = $this->createEventWithPayment();

        $this->postRegistrationPayment($event, ['email' => 'asso-' . bin2hex(random_bytes(6)) . '@example.org']);

        $this->assertResponseStatusCodeSame(200);
        $payers = $this->reload($event)->getUnrecognizedPayers();
        $this->assertCount(1, $payers);
        $this->assertSame('', $payers->first()->getLastname());
    }
}
