<?php

namespace App\Tests\Entity;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EvtTest extends TestCase
{
    private function newEvent(): Evt
    {
        return new Evt(null, null, null, null, null, null, null, 45.75, 4.85, null, null, null, null);
    }

    private function newUser(string $firstname, string $lastname): User
    {
        return (new User())->setFirstname($firstname)->setLastname($lastname);
    }

    public function testRapprocheParNomSansTenirCompteDeLaCasseDesAccentsNiDesSeparateurs(): void
    {
        $event = $this->newEvent();
        $participation = $event->addParticipation($this->newUser('Jean-Pierre', 'DUPRé'), EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);

        $this->assertSame($participation, $event->findUnpaidParticipationByName(' jean pierre', 'DUPRÉ '));
    }

    public function testInscriptionManuelleNonConfirmeeEstRapprochee(): void
    {
        $event = $this->newEvent();
        $participation = $event->addParticipation($this->newUser('Marie', 'Martin'), EventParticipation::ROLE_MANUEL, EventParticipation::STATUS_NON_CONFIRME);

        $this->assertSame($participation, $event->findUnpaidParticipationByName('Marie', 'Martin'));
    }

    public function testHomonymesNonPayesSontAmbigus(): void
    {
        $event = $this->newEvent();
        $event->addParticipation($this->newUser('Marie', 'Martin'), EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);
        $event->addParticipation($this->newUser('Marie', 'Martin'), EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);

        $this->assertNull($event->findUnpaidParticipationByName('Marie', 'Martin'));
    }

    public function testIgnoreEncadrementEtInscriptionsRefusees(): void
    {
        $event = $this->newEvent();
        $event->addParticipation($this->newUser('Marie', 'Martin'), EventParticipation::ROLE_ENCADRANT, EventParticipation::STATUS_VALIDE);
        $event->addParticipation($this->newUser('Marie', 'Martin'), EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_REFUSE);
        $participation = $event->addParticipation($this->newUser('Marie', 'Martin'), EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);

        $this->assertSame($participation, $event->findUnpaidParticipationByName('Marie', 'Martin'));
    }

    public function testParticipationDejaPayeeNestPasRapprochee(): void
    {
        $event = $this->newEvent();
        $event->addParticipation($this->newUser('Marie', 'Martin'), EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE)->setHasPaid(true);

        $this->assertNull($event->findUnpaidParticipationByName('Marie', 'Martin'));
    }

    public function testNomDePayeurVideNeRapprocheRien(): void
    {
        $event = $this->newEvent();
        $event->addParticipation(new User(), EventParticipation::ROLE_INSCRIT, EventParticipation::STATUS_VALIDE);

        $this->assertNull($event->findUnpaidParticipationByName('', ''));
    }
}
