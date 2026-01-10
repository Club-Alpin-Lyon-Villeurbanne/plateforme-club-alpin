<?php

namespace App\Tests\Controller;

use App\Entity\Evt;
use App\Tests\WebTestCase;

class IcsControllerTest extends WebTestCase
{
    public function testGlobalCalendarReturnsValidIcs(): void
    {
        $user = $this->signup();
        $event = $this->createEvent($user);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->setStatusWho($user);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $this->client->request('GET', '/calendrier.ics');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'text/calendar; charset=utf-8');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('VERSION:2.0', $content);
        $this->assertStringContainsString('BEGIN:VEVENT', $content);
        $this->assertStringContainsString('END:VEVENT', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
    }

    public function testCommissionCalendarWorks(): void
    {
        $user = $this->signup();
        $event = $this->createEvent($user);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->setStatusWho($user);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $code = $event->getCommission()->getCode();
        $this->client->request('GET', sprintf('/calendrier/%s.ics', $code));

        $this->assertResponseStatusCodeSame(200);
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString($event->getTitre(), $content);
    }

    public function testUnknownCommissionReturns404(): void
    {
        $this->client->request('GET', '/calendrier/commission-inexistante.ics');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCancelledEventsAreExcluded(): void
    {
        $user = $this->signup();
        $event = $this->createEvent($user);
        $uniqueTitle = 'Sortie annulée - ' . uniqid();
        $event->setTitre($uniqueTitle);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->setStatusWho($user);
        $event->setCancelled(true);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $this->client->request('GET', '/calendrier.ics');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString($uniqueTitle, $content);
    }

    public function testUnpublishedEventsAreExcluded(): void
    {
        $user = $this->signup();
        $event = $this->createEvent($user);
        $uniqueTitle = 'Sortie non publiée - ' . uniqid();
        $event->setTitre($uniqueTitle);
        // Status par défaut = UNSEEN (non publié)
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $this->client->request('GET', '/calendrier.ics');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString($uniqueTitle, $content);
    }

    public function testCalendarIsPubliclyAccessible(): void
    {
        $user = $this->signup();
        $event = $this->createEvent($user);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->setStatusWho($user);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        // S'assurer qu'on n'est pas connecté
        $this->signout();

        $this->client->request('GET', '/calendrier.ics');

        // Doit être accessible sans authentification
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIcsContainsEventDetails(): void
    {
        $user = $this->signup();
        $event = $this->createEvent($user);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->setStatusWho($user);
        $event->setDifficulte('PD+');
        $event->setMassif('Chartreuse');
        $event->setTarif(25.0);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $this->client->request('GET', '/calendrier.ics');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('SUMMARY:' . $event->getTitre(), $content);
        $this->assertStringContainsString('LOCATION:', $content);
        $this->assertStringContainsString('PD+', $content);
        $this->assertStringContainsString('Chartreuse', $content);
        $this->assertStringContainsString('25', $content);
    }
}
