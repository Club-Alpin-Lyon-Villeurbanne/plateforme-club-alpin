<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Montants et avertissements exposés par l'API sur une note hors sortie : calculés tant que la
 * note est chez son demandeur, figés à chaque envoi.
 */
class NoteHorsSortieMontantsTest extends ExpenseReportApiTestCase
{
    private const LIGNES = '{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines (x6)"},{"id":"ligne-2","montant":21.5,"description":"Cordelette 6 mm"}]}';

    public function testUnBrouillonExposeSesMontantsSansAvertissement(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);

        $response = $this->show($this->owner, $note);

        $this->assertResponseStatus(200, $response);
        $this->assertSame(86.4, $response['body']['totalDeclare']);
        $this->assertSame(86.4, $response['body']['totalRemboursable']);
        $this->assertSame([], $response['body']['avertissements']);
    }

    public function testUnBrouillonSansLigneVautZero(): void
    {
        $response = $this->show($this->owner, $this->creerAchat($this->owner));

        $this->assertEquals(0, $response['body']['totalDeclare']);
        $this->assertEquals(0, $response['body']['totalRemboursable']);
    }

    public function testAuDelaDuSeuilLAvertissementEstExpose(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, $this->lignesDeTotal($this->seuil() + 0.01));

        $response = $this->show($this->owner, $note);

        $this->assertSame([$this->messageDuSeuil()], $response['body']['avertissements']);
    }

    public function testAuSeuilAucunAvertissement(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, $this->lignesDeTotal($this->seuil()));

        $this->assertSame([], $this->show($this->owner, $note)['body']['avertissements']);
    }

    public function testLesAvertissementsSuiventLaSaisieDansLaReponseDuPatchEtDansLaListe(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);

        $response = $this->patch($this->owner, $note, ['details' => $this->lignesDeTotal($this->seuil() + 10)]);

        $this->assertResponseStatus(200, $response);
        $this->assertSame([$this->messageDuSeuil()], $response['body']['avertissements']);
        $this->assertEquals($this->seuil() + 10, $response['body']['totalDeclare']);

        $liste = $this->list($this->owner, '/api/notes-de-frais', ['type' => 'autre', 'inclure_brouillons' => 'true']);
        $this->assertResponseStatus(200, $liste);
        $this->assertSame([$this->messageDuSeuil()], $liste['body']['data'][0]['avertissements']);
    }

    public function testUneNoteDeSortieNExposeNiMontantsNiAvertissements(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $response = $this->show($this->owner, $report);

        $this->assertResponseStatus(200, $response);
        $this->assertArrayNotHasKey('totalDeclare', $response['body']);
        $this->assertArrayNotHasKey('totalRemboursable', $response['body']);
        $this->assertArrayNotHasKey('avertissements', $response['body']);
    }

    public function testLEnvoiFigeLesMontants(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));

        $envoyee = $this->reload($note);
        $this->assertSame('86.40', $envoyee->getTotalDeclare());
        $this->assertSame('86.40', $envoyee->getTotalRemboursable());

        $this->modifierLesLignesEnBase($note, $this->lignesDeTotal($this->seuil() + 500));

        $response = $this->show($this->owner, $note);
        $this->assertResponseStatus(200, $response);
        $this->assertSame(86.4, $response['body']['totalDeclare']);
        $this->assertSame(86.4, $response['body']['totalRemboursable']);
        $this->assertSame([], $response['body']['avertissements']);
    }

    public function testUneNoteACorrigerSuitSesLignes(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));
        $this->renvoyerAuDemandeur($note);

        $response = $this->patch($this->owner, $note, ['details' => '{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines (x6)"}]}']);

        $this->assertResponseStatus(200, $response);
        $this->assertSame(64.9, $response['body']['totalDeclare']);
    }

    public function testChaqueEnvoiFigeLesMontantsDuMoment(): void
    {
        $note = $this->creerAchat($this->owner, ExpenseReportStatusEnum::DRAFT, self::LIGNES);
        $this->joindreLesJustificatifs($note, 'ligne-1', 'ligne-2');
        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note));
        $this->renvoyerAuDemandeur($note);

        $this->assertResponseStatus(200, $this->envoyer($this->owner, $note, ['details' => '{"lignes":[{"id":"ligne-1","montant":64.9,"description":"Dégaines (x6)"}]}']));

        $this->assertSame('64.90', $this->reload($note)->getTotalDeclare());
    }

    private function seuil(): float
    {
        return (float) $this->configHorsSortie()['seuilAvertissement'];
    }

    private function messageDuSeuil(): string
    {
        return sprintf('Plus de %s € : la dépense doit être au budget de la commission, ou acceptée par la trésorerie.', (new \NumberFormatter('fr_FR', \NumberFormatter::DECIMAL))->format($this->seuil()));
    }

    /**
     * Deux lignes dont le total vaut ce montant, au centime.
     */
    private function lignesDeTotal(float $total): string
    {
        return json_encode(['lignes' => [
            ['id' => 'ligne-1', 'montant' => 100, 'description' => 'Dégaines (x10)'],
            ['id' => 'ligne-2', 'montant' => round($total - 100, 2), 'description' => 'Cordes'],
        ]]);
    }

    private function modifierLesLignesEnBase(ExpenseReport $note, string $details): void
    {
        // La colonne JSON contient la chaîne `details` elle-même, encodée en JSON.
        $this->em()->getConnection()->executeStatement('UPDATE expense_report SET details = ? WHERE id = ?', [json_encode($details), $note->getId()]);
    }

    private function renvoyerAuDemandeur(ExpenseReport $note): void
    {
        $this->assertResponseStatus(200, $this->patch($this->manager, $note, ['status' => 'rejected', 'commentaireStatut' => 'Merci de joindre la facture du magasin.']));
    }
}
