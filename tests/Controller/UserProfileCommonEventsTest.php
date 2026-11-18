<?php

namespace App\Tests\Controller;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class UserProfileCommonEventsTest extends WebTestCase
{
    private const string SECTION = 'id="user-sorties-communes"';

    private function signupWithProfileRight(): User
    {
        $user = $this->signup();
        $this->addAttribute($user, 'encadrant', 'commission:' . $this->createCommission()->getCode());

        // Recharge l'adhérent depuis la base, comme lors d'une vraie requête
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        return $em->find(User::class, $user->getId());
    }

    private function profileHtml(User $user): string
    {
        $this->client->request('GET', '/fiche-profil/' . $user->getId());
        $this->assertResponseIsSuccessful();

        return (string) $this->client->getResponse()->getContent();
    }

    public function testSectionListsCommonEventsWithProfileRight(): void
    {
        $viewer = $this->signupWithProfileRight();
        $consulted = $this->signup();

        $event = $this->createEvent($viewer);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->addParticipation($consulted, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $this->getContainer()->get(EntityManagerInterface::class)->flush();

        $this->signin($viewer);
        $html = $this->profileHtml($consulted);

        $this->assertStringContainsString(self::SECTION, $html);
        $this->assertStringContainsString('1 sortie(s) en commun', $html);
        $this->assertSame(1, substr_count($html, 'id="agenda"'));
    }

    public function testSectionStaysVisibleWithoutCommonEvent(): void
    {
        $viewer = $this->signupWithProfileRight();
        $consulted = $this->signup();

        $this->signin($viewer);
        $html = $this->profileHtml($consulted);

        $this->assertStringContainsString(self::SECTION, $html);
        $this->assertStringContainsString('0 sortie(s) en commun', $html);
    }

    public function testSectionHiddenWithoutProfileRight(): void
    {
        $viewer = $this->signup();
        $consulted = $this->signup();

        $this->signin($viewer);

        $this->assertStringNotContainsString(self::SECTION, $this->profileHtml($consulted));
    }

    public function testSectionHiddenOnOwnProfile(): void
    {
        $viewer = $this->signupWithProfileRight();

        $this->signin($viewer);

        $this->assertStringNotContainsString(self::SECTION, $this->profileHtml($viewer));
    }
}
