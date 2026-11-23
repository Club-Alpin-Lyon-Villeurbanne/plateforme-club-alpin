<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

class ManualAddSelectTest extends WebTestCase
{
    /**
     * Chaque onglet indique la source de données à charger : le changement d'onglet se fait
     * sans recharger la page, pour conserver les personnes déjà cochées.
     */
    public function testLesOngletsChargentLeurListeSansQuitterLaPage(): void
    {
        $organizer = $this->signup();
        $this->signin($organizer);
        $event = $this->createEvent($organizer);

        $crawler = $this->client->request('GET', sprintf('/selectionner-manuel/%d?show=nomade', $event->getId()));

        $this->assertResponseIsSuccessful();
        $tabs = $crawler->filter('a.manual-add-tab');
        $this->assertCount(5, $tabs);
        foreach (['allvalid', 'discovery', 'nomade', 'external', 'all'] as $index => $show) {
            $this->assertSame($show, $tabs->eq($index)->attr('data-show'));
            $this->assertSame('/users/data/manual-add/' . $show, $tabs->eq($index)->attr('data-source'));
        }
        $this->assertSame('nomade', $crawler->filter('a.manual-add-tab.active')->attr('data-show'));
    }

    /**
     * Le tableau sert seulement à choisir : le formulaire n'envoie que les sélections
     * reconstituées au moment de valider, quel que soit l'onglet affiché.
     */
    public function testLeTableauEstHorsDuFormulaireDeValidation(): void
    {
        $organizer = $this->signup();
        $this->signin($organizer);
        $event = $this->createEvent($organizer);

        $crawler = $this->client->request('GET', sprintf('/selectionner-manuel/%d', $event->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('table.datatables'));
        $this->assertCount(0, $crawler->filter('#manual-add-form table'));
        $this->assertCount(1, $crawler->filter('#manual-add-form #manual-add-selection-inputs'));
    }

    public function testUnOngletInconnuAfficheLesAdherentsDuClub(): void
    {
        $organizer = $this->signup();
        $this->signin($organizer);
        $event = $this->createEvent($organizer);

        $crawler = $this->client->request('GET', sprintf('/selectionner-manuel/%d?show=inconnu', $event->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSame('allvalid', $crawler->filter('a.manual-add-tab.active')->attr('data-show'));
    }
}
