<?php

namespace App\Tests\Repository;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use App\Repository\EvtRepository;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class EvtRepositoryCommonEventsTest extends WebTestCase
{
    private function em(): EntityManagerInterface
    {
        return $this->getContainer()->get(EntityManagerInterface::class);
    }

    private function repo(): EvtRepository
    {
        return $this->getContainer()->get(EvtRepository::class);
    }

    /**
     * Crée une sortie passée, publiée et validée, dont $owner est encadrant validé.
     */
    private function createPastValidatedEvent(User $owner): Evt
    {
        // createEvent() crée une sortie future avec $owner comme participant validé
        $event = $this->createEvent($owner);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->setStartDate(new \DateTimeImmutable('-8 days'));
        $event->setEndDate(new \DateTimeImmutable('-7 days'));
        $this->em()->flush();

        return $event;
    }

    public function testReturnsSharedPastValidatedEvent(): void
    {
        $userA = $this->signup();
        $userB = $this->signup();

        $event = $this->createPastValidatedEvent($userA);
        $event->addParticipation($userB, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $this->em()->flush();

        $common = $this->repo()->getCommonEvents($userA, $userB, 0, 10);

        $this->assertCount(1, $common);
        $this->assertSame($event->getId(), $common[0]->getId());
        $this->assertSame(1, $this->repo()->getCommonEventsCount($userA, $userB));

        // L'intersection est symétrique
        $this->assertSame(1, $this->repo()->getCommonEventsCount($userB, $userA));
    }

    public function testIncludesFutureSharedEvent(): void
    {
        $userA = $this->signup();
        $userB = $this->signup();

        // Sortie future (createEvent => +7/+8 jours), publiée et validée, deux participants validés.
        // Comme « Dernières sorties », on ne filtre pas sur la date : elle doit être incluse.
        $event = $this->createEvent($userA);
        $event->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $event->addParticipation($userB, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $this->em()->flush();

        $common = $this->repo()->getCommonEvents($userA, $userB, 0, 10);

        $this->assertCount(1, $common);
        $this->assertSame($event->getId(), $common[0]->getId());
        $this->assertSame(1, $this->repo()->getCommonEventsCount($userA, $userB));
    }

    public function testExcludesWhenOtherUserNotValidated(): void
    {
        $userA = $this->signup();
        $userB = $this->signup();

        $event = $this->createPastValidatedEvent($userA);
        // B seulement pré-inscrit (non confirmé) => ne compte pas
        $event->addParticipation($userB, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_NON_CONFIRME);
        $this->em()->flush();

        $this->assertEmpty($this->repo()->getCommonEvents($userA, $userB, 0, 10));
        $this->assertSame(0, $this->repo()->getCommonEventsCount($userA, $userB));
    }

    public function testExcludesEventNotSharedWithOtherUser(): void
    {
        $userA = $this->signup();
        $userB = $this->signup();
        $userC = $this->signup();

        // Sortie passée validée partagée par A et C, mais pas B
        $event = $this->createPastValidatedEvent($userA);
        $event->addParticipation($userC, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $this->em()->flush();

        $this->assertEmpty($this->repo()->getCommonEvents($userA, $userB, 0, 10));
        $this->assertCount(1, $this->repo()->getCommonEvents($userA, $userC, 0, 10));
    }
}
