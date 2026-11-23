<?php

namespace App\Tests\Api;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\ExpenseReport;
use App\Entity\User;

/**
 * Création d'une note de sortie (POST /api/notes-de-frais avec eventId) : réservée aux encadrants
 * de la sortie (encadrant, stagiaire, co-encadrant), à participation validée.
 */
class NoteSortieCreationTest extends ExpenseReportApiTestCase
{
    public function testUnAdherentSansLienAvecLaSortieNePeutPasCreerSaNote(): void
    {
        $sortie = $this->createEvent($this->owner);

        $this->assertResponseStatus(403, $this->create($this->stranger, ['eventId' => $sortie->getId()]));
        $this->assertNull($this->noteDe($this->stranger, $sortie));
    }

    public function testUnParticipantNonEncadrantNePeutPasCreerSaNote(): void
    {
        // Le bénévole d'encadrement n'est pas un encadrant : ROLES_ENCADREMENT_ETENDU ne suffirait pas.
        foreach ([EventParticipation::ROLE_INSCRIT, EventParticipation::ROLE_BENEVOLE] as $role) {
            $participant = $this->signup();
            $sortie = $this->createEvent($this->signup());
            $this->inscrire($sortie, $participant, $role, EventParticipation::STATUS_VALIDE);

            $this->assertResponseStatus(403, $this->create($participant, ['eventId' => $sortie->getId()]));
            $this->assertNull($this->noteDe($participant, $sortie), $role);
        }
    }

    public function testUnEncadrantDontLaParticipationNEstPasValideeNePeutPasCreerSaNote(): void
    {
        $sortie = $this->createEvent($this->owner);
        $this->inscrire($sortie, $this->adherent, EventParticipation::ROLE_ENCADRANT, EventParticipation::STATUS_NON_CONFIRME);

        $this->assertResponseStatus(403, $this->create($this->adherent, ['eventId' => $sortie->getId()]));
    }

    public function testLEncadrantDeLaSortieCreeSaNote(): void
    {
        $sortie = $this->createEvent($this->owner);

        $response = $this->create($this->owner, ['eventId' => $sortie->getId()]);

        $this->assertResponseStatus(201, $response);
        $this->assertSame('draft', $response['body']['status']);
    }

    public function testLeCoEncadrantEtLeStagiaireCreentLeurNote(): void
    {
        foreach ([EventParticipation::ROLE_COENCADRANT, EventParticipation::ROLE_STAGIAIRE] as $role) {
            $participant = $this->signup();
            $sortie = $this->createEvent($this->signup());
            $this->inscrire($sortie, $participant, $role, EventParticipation::STATUS_VALIDE);

            $this->assertResponseStatus(201, $this->create($participant, ['eventId' => $sortie->getId()]));
        }
    }

    public function testUneSortieInconnueResteIntrouvable(): void
    {
        $this->assertResponseStatus(404, $this->create($this->owner, ['eventId' => 999999999]));
    }

    private function inscrire(Evt $sortie, User $user, string $role, int $statut): void
    {
        $em = $this->em();
        $sortie = $em->find(Evt::class, $sortie->getId());
        $sortie->addParticipation($em->find(User::class, $user->getId()), $role, $statut);
        $em->flush();
        $em->clear();
    }

    private function noteDe(User $user, Evt $sortie): ?object
    {
        return $this->em()->getRepository(ExpenseReport::class)->findOneBy(['user' => $user->getId(), 'event' => $sortie->getId()]);
    }
}
