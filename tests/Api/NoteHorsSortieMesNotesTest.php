<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Données de la page « Mes notes de frais » : liste filtrée sur les types hors sortie, date
 * d'envoi et valideurs d'une note.
 */
class NoteHorsSortieMesNotesTest extends ExpenseReportApiTestCase
{
    private const LIGNES = '{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines (x6)"}]}';

    public function testLaListeFiltreeRenvoieSesNotesHorsSortieBrouillonsCompris(): void
    {
        $brouillon = $this->creerAchat($this->owner);
        $envoyee = $this->creerAchat($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $response = $this->listerMesNotes($this->owner);

        $this->assertResponseStatus(200, $response);
        $this->assertEqualsCanonicalizing([$brouillon->getId(), $envoyee->getId()], array_column($response['body']['data'], 'id'));
    }

    public function testLaListeNeContientNiSesNotesDeSortieNiLesNotesDesAutres(): void
    {
        $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);
        $this->creerAchat($this->stranger);
        $mienne = $this->creerAchat($this->owner);

        $response = $this->listerMesNotes($this->owner);

        $this->assertSame([$mienne->getId()], array_column($response['body']['data'], 'id'));
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

    private function listerMesNotes(User $utilisateur): array
    {
        return $this->list($utilisateur, '/api/notes-de-frais', ['type' => ['autre', 'formation'], 'inclure_brouillons' => 'true']);
    }
}
