<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Ce que le responsable d'une note hors sortie lit : la note et ses justificatifs, sans le choix
 * entre don et remboursement ; et qui sont les valideurs exposés.
 */
class NoteHorsSortieResponsableLectureTest extends ExpenseReportApiTestCase
{
    public function testLeResponsableLitLaNoteSansLeChoixEntreDonEtRemboursement(): void
    {
        $note = $this->noteEnvoyee($this->adherent, false, commission: $this->commissionDeTest);

        $response = $this->show($this->responsable, $note);

        $this->assertResponseStatus(200, $response);
        $this->assertSame('Dégaines pour la SAE', $response['body']['objet']);
        $this->assertSame(86.4, $response['body']['totalDeclare']);
        $this->assertArrayNotHasKey('refundRequired', $response['body']);
    }

    /**
     * @dataProvider voitLeChoixProvider
     */
    public function testLeProprietaireEtLeGestionnaireVoientLeChoix(string $acteur): void
    {
        $note = $this->noteEnvoyee($this->adherent, false, commission: $this->commissionDeTest);

        $response = $this->show($this->{$acteur}, $note);

        $this->assertResponseStatus(200, $response);
        $this->assertArrayHasKey('refundRequired', $response['body']);
        $this->assertFalse($response['body']['refundRequired']);
    }

    public static function voitLeChoixProvider(): iterable
    {
        yield 'propriétaire' => ['adherent'];
        yield 'gestionnaire' => ['manager'];
    }

    public function testUnGestionnaireResponsableVoitLeChoix(): void
    {
        $this->rendreResponsable($this->manager, $this->commissionDeTest);
        $note = $this->noteEnvoyee($this->adherent, false, commission: $this->commissionDeTest);

        $this->assertFalse($this->show($this->manager, $note)['body']['refundRequired']);
    }

    public function testLaReponseALaDecisionDuResponsableNeContientPasLeChoix(): void
    {
        $note = $this->noteEnvoyee($this->adherent, false, commission: $this->commissionDeTest);

        $response = $this->patch($this->responsable, $note, ['status' => 'preapproved']);

        $this->assertResponseStatus(200, $response);
        $this->assertArrayNotHasKey('refundRequired', $response['body']);
    }

    /**
     * Refuser un choix différent de celui enregistré, et accepter le même, le dévoilerait.
     *
     * @dataProvider choixProvider
     */
    public function testLeChoixEnvoyeParLeResponsableEstIgnore(bool $choix): void
    {
        $note = $this->noteEnvoyee($this->adherent, true, commission: $this->commissionDeTest);

        $response = $this->patch($this->responsable, $note, ['status' => 'preapproved', 'refundRequired' => $choix]);

        $this->assertResponseStatus(200, $response);
        $this->assertTrue($this->reload($note)->isRefundRequired());
    }

    public static function choixProvider(): iterable
    {
        yield 'le même choix' => [true];
        yield 'un autre choix' => [false];
    }

    public function testLeResponsableLitLesJustificatifs(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $response = $this->listAttachments($this->responsable, $note);

        $this->assertResponseStatus(200, $response);
        $this->assertCount(2, $response['body']['data']);
    }

    /**
     * @dataProvider sansDroitProvider
     */
    public function testSansEtreValideurOnNeLitPasLesJustificatifs(string $acteur): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $response = $this->listAttachments($this->{$acteur}, $note);

        $this->assertResponseStatus(200, $response);
        $this->assertSame([], $response['body']['data']);
    }

    public static function sansDroitProvider(): iterable
    {
        yield 'responsable d\'une autre commission' => ['responsableAutreCommission'];
        yield 'encadrant de la commission' => ['encadrant'];
    }

    /**
     * @dataProvider statutModifiableProvider
     */
    public function testLEnvoiDUnJustificatifEstRefuseAuResponsable(ExpenseReportStatusEnum $statut): void
    {
        $note = $this->creerAchat($this->adherent, $statut, self::LIGNES_ACHAT, $this->commissionDeTest);

        $this->assertResponseStatus(404, $this->uploadAttachment($this->responsable, $note, 'ligne-1'));
        $this->assertCount(0, $this->reload($note)->getAttachments());
    }

    public static function statutModifiableProvider(): iterable
    {
        yield 'brouillon' => [ExpenseReportStatusEnum::DRAFT];
        yield 'à corriger' => [ExpenseReportStatusEnum::REJECTED];
    }

    public function testLesValideursSontLesResponsablesSaufLeDemandeur(): void
    {
        $deLAdherent = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $duResponsable = $this->noteEnvoyee($this->responsable, commission: $this->commissionDeTest);

        $this->assertSame([['Alex', 'DURAND'], ['Sam', 'PETIT']], $this->valideurs($this->adherent, $deLAdherent));
        $this->assertSame([['Sam', 'PETIT']], $this->valideurs($this->responsable, $duResponsable));
        $this->assertSame($this->coResponsable->getId(), $this->show($this->responsable, $duResponsable)['body']['valideurs'][0]['id']);
    }

    public function testSansAutreResponsableQueLeDemandeurLaTresorerieValide(): void
    {
        $note = $this->noteEnvoyee($this->responsableAutreCommission, commission: $this->autreCommission);

        $this->assertSame([], $this->valideurs($this->responsableAutreCommission, $note));
        $this->assertFalse($this->show($this->responsableAutreCommission, $note)['body']['peutDecider']);
    }

    public function testUneCommissionSansResponsableVaALaTresorerie(): void
    {
        $note = $this->noteEnvoyee($this->adherent);

        $this->assertSame([], $this->valideurs($this->adherent, $note));
    }

    /**
     * @dataProvider inactifProvider
     */
    public function testUnResponsableInactifNEstPlusValideur(string $inactivite, int $refus): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $responsable = $this->em()->find(User::class, $this->responsable->getId());
        'supprime' === $inactivite ? $responsable->setIsDeleted(true) : $responsable->setDoitRenouveler(true);
        $this->em()->flush();

        $this->assertSame([['Sam', 'PETIT']], $this->valideurs($this->adherent, $note));
        $this->assertResponseStatus($refus, $this->show($this->responsable, $note));
    }

    public static function inactifProvider(): iterable
    {
        yield 'doit renouveler' => ['doitRenouveler', 403];
        // Un compte supprimé ne se connecte même plus.
        yield 'supprimé' => ['supprime', 401];
    }

    public function testLeResponsableLitUnBrouillonDeSaCommissionQuiNEstPasAValider(): void
    {
        $brouillon = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::DRAFT, self::LIGNES_ACHAT, $this->commissionDeTest);
        $this->em()->find(ExpenseReport::class, $brouillon->getId())->setRefundRequired(false);
        $this->em()->flush();

        $response = $this->show($this->responsable, $brouillon);

        $this->assertResponseStatus(200, $response);
        $this->assertArrayNotHasKey('refundRequired', $response['body']);
        foreach ([[], ['status' => 'draft', 'inclure_brouillons' => 'true']] as $filtres) {
            $liste = $this->list($this->responsable, '/api/notes-de-frais/a-valider', $filtres);
            $this->assertResponseStatus(200, $liste);
            $this->assertNotContains($brouillon->getId(), array_column($liste['body']['data'], 'id'));
        }
    }

    public function testLesValideursSontRecalculesALaLecture(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->autreCommission);
        $nouveau = $this->signup();
        $this->nommer($nouveau, 'Lou', 'Martin');

        $this->rendreResponsable($nouveau, $this->autreCommission);

        $this->assertSame([['Camille', 'ROUX'], ['Lou', 'MARTIN']], $this->valideurs($this->adherent, $note));
        $this->assertSame(200, $this->show($nouveau, $note)['status']);
    }

    public function testLaDerniereDecisionNommeLeResponsable(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertResponseStatus(200, $this->patch($this->coResponsable, $note, ['status' => 'preapproved']));

        $decision = $this->show($this->adherent, $note)['body']['derniereDecision'];

        $this->assertSame(['preapproved', 'Sam', 'PETIT'], [$decision['statut'], $decision['auteur']['prenom'], $decision['auteur']['nom']]);
    }

    /**
     * @return list<array{string, string}>
     */
    private function valideurs(User $as, ExpenseReport $note): array
    {
        $response = $this->show($as, $note);
        $this->assertResponseStatus(200, $response);

        return array_map(static fn (array $valideur) => [$valideur['prenom'], $valideur['nom']], $response['body']['valideurs']);
    }
}
