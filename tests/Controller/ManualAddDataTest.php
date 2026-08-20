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
        }
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
        $this->signin($this->signup());
        $this->client->request('GET', '/users/data/manual-add/allvalid', $this->datatablesQuery(0));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListeDesAdherentsResteReserveeAuDroitDedie(): void
    {
        $this->signin($this->signup());
        $this->client->request('GET', '/users/data/users-list/allvalid', $this->datatablesQuery(0));

        $this->assertResponseStatusCodeSame(403);
    }
}
