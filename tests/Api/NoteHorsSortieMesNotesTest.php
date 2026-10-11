<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Données de la page « Mes notes de frais » : toutes les notes du demandeur, brouillons compris,
 * filtrables par type ; date d'envoi et valideurs d'une note hors sortie.
 */
class NoteHorsSortieMesNotesTest extends ExpenseReportApiTestCase
{
    private const LIGNES = '{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines (x6)"}]}';

    public function testLaListeRenvoieToutesSesNotesBrouillonsCompris(): void
    {
        $sortie = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);
        $brouillon = $this->creerAchat($this->owner);
        $envoyee = $this->creerAchat($this->owner, ExpenseReportStatusEnum::SUBMITTED);
        $this->creerAchat($this->stranger);

        $response = $this->listerMesNotes($this->owner);

        $this->assertResponseStatus(200, $response);
        $this->assertEqualsCanonicalizing([$sortie->getId(), $brouillon->getId(), $envoyee->getId()], array_column($response['body']['data'], 'id'));
    }

    public function testLaListeSeFiltreParType(): void
    {
        $sortie = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);
        $achat = $this->creerAchat($this->owner);
        $formation = $this->creerFormation($this->owner);

        foreach (['sortie' => $sortie, 'autre' => $achat, 'formation' => $formation] as $type => $note) {
            $this->assertSame([$note->getId()], array_column($this->listerMesNotes($this->owner, ['type' => $type])['body']['data'], 'id'), $type);
        }
    }

    public function testUneNoteEnvoyeeExposeSaDateDEnvoiEtSesValideurs(): void
    {
        $note = $this->creerAchat($this->owner);
        $this->joindreLesJustificatifs($note, 'ligne-1');
        $avant = new \DateTimeImmutable('-1 minute');
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note, ['details' => self::LIGNES, 'refundRequired' => true]));

        $envoyee = $this->listerMesNotes($this->owner)['body']['data'][0];

        $this->assertGreaterThanOrEqual($avant, new \DateTimeImmutable($envoyee['dateEnvoi']));
        $this->assertSame([], $envoyee['valideurs']);
    }

    public function testUnBrouillonNAPasDeDateDEnvoi(): void
    {
        $brouillon = $this->creerAchat($this->owner);

        $lue = $this->show($this->owner, $brouillon);

        $this->assertResponseStatus(200, $lue);
        $this->assertNull($lue['body']['dateEnvoi']);
        $this->assertSame([], $lue['body']['valideurs']);
    }

    public function testUneNoteDeSortieNAPasCesChamps(): void
    {
        $note = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $lue = $this->show($this->owner, $note);

        $this->assertArrayNotHasKey('dateEnvoi', $lue['body']);
        $this->assertArrayNotHasKey('valideurs', $lue['body']);
    }

    private function listerMesNotes(User $utilisateur, array $filtres = []): array
    {
        return $this->list($utilisateur, '/api/notes-de-frais', ['inclure_brouillons' => 'true'] + $filtres);
    }
}
