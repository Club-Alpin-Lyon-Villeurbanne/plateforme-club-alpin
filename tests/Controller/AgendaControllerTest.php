<?php

namespace App\Tests\Controller;

use App\Entity\Evt;
use App\Tests\WebTestCase;

class AgendaControllerTest extends WebTestCase
{
    public function testAgendaDisplaysIcsSubscribeButton(): void
    {
        $crawler = $this->client->request('GET', '/agenda.html');

        $this->assertResponseIsSuccessful();

        // Vérifie que le bouton d'abonnement est présent
        $this->assertSelectorExists('.subscribe-calendar-wrapper button');
        $this->assertSelectorTextContains('.subscribe-calendar-wrapper button', "S'abonner au calendrier du club");

        // Vérifie que l'URL ICS globale est correcte
        $icsUrl = $crawler->filter('.subscribe-calendar-copy input')->attr('value');
        $this->assertStringContainsString('/calendrier.ics', $icsUrl);
    }

    public function testCommissionAgendaDisplaysCommissionSpecificIcsButton(): void
    {
        $user = $this->signup();
        $event = $this->createEvent($user);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->setStatusWho($user);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $commission = $event->getCommission();
        $code = $commission->getCode();

        $crawler = $this->client->request('GET', sprintf('/agenda/%s.html', $code));

        $this->assertResponseIsSuccessful();

        // Vérifie qu'il y a deux boutons (global + commission)
        $buttons = $crawler->filter('.subscribe-calendar-wrapper');
        $this->assertEquals(2, $buttons->count());

        // Vérifie que le bouton commission a le bon titre
        $this->assertStringContainsString($commission->getTitle(), $buttons->eq(1)->text());

        // Vérifie que l'URL ICS de la commission est correcte
        $commissionIcsUrl = $crawler->filter('.subscribe-calendar-copy input')->eq(1)->attr('value');
        $this->assertStringContainsString(sprintf('/calendrier/%s.ics', $code), $commissionIcsUrl);
    }
}
