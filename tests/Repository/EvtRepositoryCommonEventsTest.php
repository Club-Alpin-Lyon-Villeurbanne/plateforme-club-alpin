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

    public function testExcludesWhenConsultedUserNotValidated(): void
    {
        $userA = $this->signup();
        $userB = $this->signup();

        // A est pré-inscrit (non confirmé) sur une sortie de B : le filtre vaut des deux côtés
        $event = $this->createPastValidatedEvent($userB);
        $event->addParticipation($userA, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_NON_CONFIRME);
        $this->em()->flush();

        $this->assertEmpty($this->repo()->getCommonEvents($userA, $userB, 0, 10));
        $this->assertSame(0, $this->repo()->getCommonEventsCount($userA, $userB));
    }

    public function testExcludesCancelledEvent(): void
    {
        $userA = $this->signup();
        $userB = $this->signup();

        // L'annulation conserve les participations de l'encadrement
        $event = $this->createPastValidatedEvent($userA);
        $event->addParticipation($userB, EventParticipation::ROLE_COENCADRANT, EventParticipation::STATUS_VALIDE);
        $event->setCancelled(true);
        $this->em()->flush();

        $this->assertEmpty($this->repo()->getCommonEvents($userA, $userB, 0, 10));
        $this->assertSame(0, $this->repo()->getCommonEventsCount($userA, $userB));
    }

    public function testExcludesUnpublishedEvent(): void
    {
        $userA = $this->signup();
        $userB = $this->signup();

        $event = $this->createEvent($userA);
        $event->setStatus(Evt::STATUS_PUBLISHED_UNSEEN);
        $event->addParticipation($userB, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $this->em()->flush();

        $this->assertEmpty($this->repo()->getCommonEvents($userA, $userB, 0, 10));
        $this->assertSame(0, $this->repo()->getCommonEventsCount($userA, $userB));
    }

    public function testLoadsAllParticipationsOfTheEvent(): void
    {
        $userA = $this->signup();
        $userB = $this->signup();
        $userC = $this->signup();

        $event = $this->createPastValidatedEvent($userA);
        $event->addParticipation($userB, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $event->addParticipation($userC, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $this->em()->flush();
        $this->em()->clear();

        $userA = $this->em()->find(User::class, $userA->getId());
        $userB = $this->em()->find(User::class, $userB->getId());

        // Le témoin de places de la ligne de sortie compte toutes les participations validées
        $common = $this->repo()->getCommonEvents($userA, $userB, 0, 10);

        $this->assertCount(1, $common);
        $this->assertSame(3, $common[0]->getParticipationsCount());
    }

    public function testLimitAppliesToEventsNotParticipations(): void
    {
        $userA = $this->signup();
        $userB = $this->signup();

        // La sortie la plus récente porte plusieurs participations validées de A
        $latest = $this->createEvent($userA);
        $latest->setStatus(Evt::STATUS_PUBLISHED_VALIDE);
        $latest->setStartDate(new \DateTimeImmutable('+30 days'));
        $latest->setEndDate(new \DateTimeImmutable('+31 days'));
        $latest->addParticipation($userA, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $latest->addParticipation($userA, EventParticipation::ROLE_BENEVOLE, EventParticipation::STATUS_VALIDE);
        $latest->addParticipation($userB, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);

        foreach ([1, 2, 3] as $i) {
            $event = $this->createPastValidatedEvent($userA);
            $event->addParticipation($userB, EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        }
        $this->em()->flush();

        $this->assertCount(3, $this->repo()->getCommonEvents($userA, $userB, 0, 3));
        $this->assertSame(4, $this->repo()->getCommonEventsCount($userA, $userB));
    }
}
