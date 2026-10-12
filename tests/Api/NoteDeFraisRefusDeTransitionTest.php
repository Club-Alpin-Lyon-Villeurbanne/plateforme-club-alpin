<?php

namespace App\Tests\Api;

use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Un changement de statut refusé dit pourquoi, en français : compta-club et le site affichent le
 * message tel quel.
 */
class NoteDeFraisRefusDeTransitionTest extends ExpenseReportApiTestCase
{
    private const ATTEND_LE_RESPONSABLE = 'Cette demande attend encore la décision du responsable de la commission.';
    private const PROPRE_NOTE = 'Vous ne pouvez pas décider sur votre propre note de frais.';
    private const NON_PERMIS = 'Ce changement de statut n\'est pas permis.';

    /**
     * @dataProvider decisionsProvider
     */
    public function testLaTresorerieApprendQueLaNoteAttendSonResponsable(array $decision): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertRefusAvec($this->patch($this->manager, $note, $decision), 'status', self::ATTEND_LE_RESPONSABLE);
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($note)->getStatus());
    }

    public static function decisionsProvider(): iterable
    {
        yield 'validation' => [['status' => 'approved']];
        yield 'refus' => [['status' => 'rejected', 'commentaireStatut' => 'Facture illisible.']];
    }

    public function testUnGestionnaireNeDecidePasSurSaPropreNoteHorsSortie(): void
    {
        $note = $this->noteEnvoyee($this->manager);

        $this->assertRefusAvec($this->patch($this->manager, $note, ['status' => 'approved']), 'status', self::PROPRE_NOTE);
    }

    public function testUnGestionnaireNeDecidePasSurSaPropreNoteDeSortie(): void
    {
        $note = $this->createReport($this->manager, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertRefusAvec($this->patch($this->manager, $note, ['status' => 'approved']), 'status', self::PROPRE_NOTE);
    }

    public function testUnResponsableNeValidePasSaPropreNote(): void
    {
        $note = $this->noteEnvoyee($this->responsable, commission: $this->commissionDeTest);

        $this->assertRefusAvec($this->patch($this->responsable, $note, ['status' => 'preapproved']), 'status', self::PROPRE_NOTE);
    }

    public function testLaTresorerieNeComptabilisePasUneNoteDeSortieNonValidee(): void
    {
        $note = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertRefusAvec($this->patch($this->manager, $note, ['status' => 'accounted']), 'status', self::NON_PERMIS);
    }

    public function testLeResponsableNeValidePasALaPlaceDeLaTresorerie(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertRefusAvec($this->patch($this->responsable, $note, ['status' => 'approved']), 'status', self::NON_PERMIS);
    }
}
