<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Liste « notes de frais à valider » d'un responsable : les notes hors sortie envoyées de ses
 * commissions, déjà traitées avec le filtre de statut.
 */
class NoteHorsSortieResponsableAValiderTest extends ExpenseReportApiTestCase
{
    // Le filtre de la page « Demandes déjà traitées ».
    private const TRAITEES = ['status' => ['preapproved', 'approved', 'rejected', 'accounted']];

    /**
     * @dataProvider responsablesProvider
     */
    public function testLeResponsableVoitLesNotesEnvoyeesDeSaCommission(string $acteur): void
    {
        $deSaCommission = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED, null, $this->commissionDeTest);
        $dUneAutre = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED, null, $this->autreCommission);
        $sansResponsable = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED);

        $response = $this->aValider($this->{$acteur});

        $this->assertSame([$deSaCommission->getId()], $this->ids($response));
        $this->assertEquals(1, $response['body']['meta']['total']);
        $this->assertNotContains($dUneAutre->getId(), $this->ids($response));
        $this->assertNotContains($sansResponsable->getId(), $this->ids($response));
    }

    public static function responsablesProvider(): iterable
    {
        yield 'responsable' => ['responsable'];
        yield 'co-responsable' => ['coResponsable'];
    }

    public function testParDefautNiBrouillonNiNoteTraiteeNiNoteDeSortie(): void
    {
        $sortie = $this->createReport($this->adherent, ExpenseReportStatusEnum::SUBMITTED);
        $this->rendreResponsable($this->responsable, $sortie->getEvent()->getCommission());
        foreach ([ExpenseReportStatusEnum::DRAFT, ExpenseReportStatusEnum::PREAPPROVED, ExpenseReportStatusEnum::APPROVED, ExpenseReportStatusEnum::REJECTED, ExpenseReportStatusEnum::ACCOUNTED] as $statut) {
            $this->creerAchat($this->adherent, $statut, null, $this->commissionDeTest);
        }
        $envoyee = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED, null, $this->commissionDeTest);

        $this->assertSame([$envoyee->getId()], $this->ids($this->aValider($this->responsable)));
    }

    public function testAvecLeFiltreLeResponsableRetrouveLesNotesTraiteesDeSesCommissions(): void
    {
        $traitees = array_map(fn (ExpenseReportStatusEnum $statut) => $this->creerAchat($this->adherent, $statut, null, $this->commissionDeTest)->getId(), [
            ExpenseReportStatusEnum::PREAPPROVED,
            ExpenseReportStatusEnum::APPROVED,
            ExpenseReportStatusEnum::REJECTED,
            ExpenseReportStatusEnum::ACCOUNTED,
        ]);
        $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED, null, $this->commissionDeTest);
        $this->creerAchat($this->adherent, ExpenseReportStatusEnum::APPROVED, null, $this->autreCommission);
        $this->creerAchat($this->adherent, ExpenseReportStatusEnum::APPROVED);

        $ids = $this->ids($this->aValider($this->responsable, self::TRAITEES));

        $this->assertEqualsCanonicalizing($traitees, $ids);
    }

    public function testUneNoteValideeDisparaitDeLaListeDuCoResponsable(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->assertSame([$note->getId()], $this->ids($this->aValider($this->coResponsable)));

        $this->assertResponseStatus(200, $this->patch($this->responsable, $note, ['status' => 'preapproved']));

        $this->assertSame([], $this->ids($this->aValider($this->coResponsable)));
        $this->assertSame([$note->getId()], $this->ids($this->aValider($this->coResponsable, self::TRAITEES)));
    }

    public function testLeResponsableNeVoitPasSaPropreNote(): void
    {
        $sienne = $this->creerAchat($this->responsable, ExpenseReportStatusEnum::SUBMITTED, null, $this->commissionDeTest);

        $this->assertSame([], $this->ids($this->aValider($this->responsable)));
        $this->assertSame([$sienne->getId()], $this->ids($this->aValider($this->coResponsable)));
    }

    /**
     * @dataProvider sansDroitEtFiltresProvider
     */
    public function testSansEtreValideurLaListeEstVide(string $acteur, array $filtres): void
    {
        $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED, null, $this->commissionDeTest);
        $this->creerAchat($this->adherent, ExpenseReportStatusEnum::APPROVED, null, $this->commissionDeTest);

        $response = $this->aValider($this->{$acteur}, $filtres);

        $this->assertSame([], $this->ids($response));
        $this->assertEquals(0, $response['body']['meta']['total']);
    }

    public static function sansDroitEtFiltresProvider(): iterable
    {
        foreach (['encadrant' => 'encadrant', 'tiers' => 'stranger', 'adhérent sans rôle' => 'adherent'] as $nom => $acteur) {
            yield $nom . ', sans filtre' => [$acteur, []];
            yield $nom . ', demandes déjà traitées' => [$acteur, self::TRAITEES];
        }
    }

    /**
     * @dataProvider filtresProvider
     */
    public function testLeResponsableDUneAutreCommissionNeVoitPasLaNote(array $filtres): void
    {
        $note = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED, null, $this->commissionDeTest);
        $traitee = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::APPROVED, null, $this->commissionDeTest);

        $ids = $this->ids($this->aValider($this->responsableAutreCommission, $filtres));

        $this->assertNotContains($note->getId(), $ids);
        $this->assertNotContains($traitee->getId(), $ids);
    }

    public static function filtresProvider(): iterable
    {
        yield 'sans filtre' => [[]];
        yield 'demandes déjà traitées' => [self::TRAITEES];
    }

    public function testLaListeDonneCeQueLaPageAffiche(): void
    {
        $this->nommer($this->adherent, 'Lou', 'Martin');
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $listee = $this->aValider($this->responsable)['body']['data'][0];

        $this->assertSame($note->getId(), $listee['id']);
        $this->assertSame('Dégaines pour la SAE', $listee['objet']);
        $this->assertSame('autre', $listee['type']);
        $this->assertSame('Commission de test', $listee['commission']['title']);
        $this->assertSame(['Lou', 'MARTIN'], [$listee['utilisateur']['prenom'], $listee['utilisateur']['nom']]);
        $this->assertSame(86.4, $listee['totalDeclare']);
    }

    public function testLaListeDuResponsableNeDonnePasLeChoixEntreDonEtRemboursement(): void
    {
        $note = $this->noteEnvoyee($this->adherent, false, commission: $this->commissionDeTest);

        $listee = $this->aValider($this->responsable)['body']['data'][0];

        $this->assertSame($note->getId(), $listee['id']);
        $this->assertArrayNotHasKey('refundRequired', $listee);
        $this->assertTrue($listee['peutDecider']);
    }

    /**
     * Le filtre sur le choix trierait les notes selon la valeur cachée au responsable.
     *
     * @dataProvider choixProvider
     */
    public function testLeFiltreSurLeChoixNeRenseignePasLeResponsable(string $choix): void
    {
        $this->noteEnvoyee($this->adherent, true, commission: $this->commissionDeTest);
        $this->noteEnvoyee($this->adherent, false, commission: $this->commissionDeTest);

        $this->assertSame([], $this->ids($this->aValider($this->responsable, ['refundRequired' => $choix])));
    }

    public static function choixProvider(): iterable
    {
        yield 'remboursement' => ['true'];
        yield 'don' => ['false'];
    }

    private function aValider(User $as, array $filtres = []): array
    {
        return $this->list($as, '/api/notes-de-frais/a-valider', $filtres);
    }

    /**
     * @return list<int>
     */
    private function ids(array $response): array
    {
        $this->assertResponseStatus(200, $response);

        return array_column($response['body']['data'], 'id');
    }
}
