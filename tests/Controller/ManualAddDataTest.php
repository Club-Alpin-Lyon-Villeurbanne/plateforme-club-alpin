<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

class ManualAddDataTest extends WebTestCase
{
    /**
     * Paramètres envoyés par DataTables en mode serverSide.
     */
    private function datatablesQuery(int $eventId): array
    {
        return [
            'draw' => 1,
            'start' => 0,
            'length' => 100,
            'search' => ['value' => '', 'regex' => 'false'],
            'order' => [['column' => 2, 'dir' => 'asc']],
            'event' => $eventId,
        ];
    }

    public function testOrganisateurSansDroitSurLaListeDesAdherentsChargeLeTableau(): void
    {
        $organizer = $this->signup();
        $this->signin($organizer);
        $event = $this->createEvent($organizer);

        $this->client->request('GET', '/users/data/manual-add/allvalid', $this->datatablesQuery($event->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testLeTableauNExposePasLesCoordonneesDesAdherents(): void
    {
        $organizer = $this->signup();
        $this->signin($organizer);
        $event = $this->createEvent($organizer);

        $this->client->request('GET', '/users/data/manual-add/allvalid', $this->datatablesQuery($event->getId()));

        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($payload['data']);
        foreach ($payload['data'] as $line) {
            $this->assertArrayNotHasKey('email', $line);
            $this->assertArrayNotHasKey('tel', $line);
            $this->assertArrayNotHasKey('tools', $line);
        }
    }

    /**
     * Sans user_read_private, l'âge reste masqué : la sortie ne donne pas accès aux dates de naissance.
     */
    public function testLAgeResteMasqueSansDroitSurLesDonneesPrivees(): void
    {
        $organizer = $this->signup();
        $this->signin($organizer);
        $event = $this->createEvent($organizer);

        $this->client->request('GET', '/users/data/manual-add/allvalid', $this->datatablesQuery($event->getId()));

        $payload = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($payload['data']);
        foreach ($payload['data'] as $line) {
            $this->assertStringContainsString('lock_gray.png', $line['age']);
            $this->assertDoesNotMatchRegularExpression('/\d+\s*ans/', $line['age']);
        }
    }

    /**
     * Les comptes supprimés ne sont proposés que par la liste des adhérents, jamais à l'inscription.
     */
    public function testLaVueDesComptesSupprimesEstRefusee(): void
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $supprime = $this->signup();
        $supprime->setIsDeleted(true);
        $em->flush();

        $organizer = $this->signup();
        $this->signin($organizer);
        $event = $this->createEvent($organizer);

        $this->client->request('GET', '/users/data/manual-add/deleted', $this->datatablesQuery($event->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @dataProvider vuesDeLEcranDInscriptionManuelle
     */
    public function testLesVuesDeLEcranRestentAccessibles(string $show): void
    {
        $organizer = $this->signup();
        $this->signin($organizer);
        $event = $this->createEvent($organizer);

        $this->client->request('GET', '/users/data/manual-add/' . $show, $this->datatablesQuery($event->getId()));

        $this->assertResponseIsSuccessful();
    }

    public static function vuesDeLEcranDInscriptionManuelle(): iterable
    {
        yield 'adhérents club' => ['allvalid'];
        yield 'cartes découverte' => ['discovery'];
        yield 'adhérents autres clubs' => ['nomade'];
        yield 'personnes externes' => ['external'];
        yield 'tous' => ['all'];
    }

    public function testAdherentSansLienAvecLaSortieEstRefuse(): void
    {
        $organizer = $this->signup();
        $event = $this->createEvent($organizer);

        $this->signin($this->signup());
        $this->client->request('GET', '/users/data/manual-add/allvalid', $this->datatablesQuery($event->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testSortieInconnueEstRefusee(): void
    {
        $organizer = $this->signup();
        $this->signin($organizer);
        $this->createEvent($organizer);

        $this->client->request('GET', '/users/data/manual-add/allvalid', $this->datatablesQuery(0));

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Le droit sur une sortie n'ouvre pas la liste des adhérents.
     */
    public function testListeDesAdherentsResteReserveeAuDroitDedie(): void
    {
        $organizer = $this->signup();
        $this->signin($organizer);
        $event = $this->createEvent($organizer);

        $this->client->request('GET', '/users/data/users-list/allvalid', $this->datatablesQuery($event->getId()));

        $this->assertResponseStatusCodeSame(403);
    }
}
