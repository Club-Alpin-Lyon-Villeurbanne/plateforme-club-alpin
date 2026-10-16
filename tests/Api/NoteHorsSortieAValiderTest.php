<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * « Notes de frais à valider » ne donne rien de plus à un gestionnaire : la trésorerie travaille
 * dans compta-club. Il n'y voit que les notes des commissions dont il est responsable.
 */
class NoteHorsSortieAValiderTest extends ExpenseReportApiTestCase
{
    /**
     * @dataProvider filtresProvider
     */
    public function testUnGestionnaireQuiNEstPasResponsableRecoitUneListeVide(array $filtres): void
    {
        $this->creerAchat($this->owner, ExpenseReportStatusEnum::SUBMITTED);
        $this->creerAchat($this->owner, ExpenseReportStatusEnum::APPROVED);
        $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED, null, $this->commissionDeTest);

        $response = $this->aValider($this->manager, $filtres);

        $this->assertSame([], $this->ids($response));
        $this->assertEquals(0, $response['body']['meta']['total']);
    }

    public static function filtresProvider(): iterable
    {
        yield 'sans filtre' => [[]];
        yield 'demandes déjà traitées' => [['status' => ['preapproved', 'approved', 'rejected', 'accounted']]];
    }

    public function testUnGestionnaireResponsableVoitCeQueVoitLeResponsable(): void
    {
        $this->rendreResponsable($this->manager, $this->commissionDeTest);
        $deSaCommission = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED, null, $this->commissionDeTest);
        $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED, null, $this->autreCommission);
        $this->creerAchat($this->adherent, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertSame([$deSaCommission->getId()], $this->ids($this->aValider($this->manager)));
        $this->assertSame($this->ids($this->aValider($this->responsable)), $this->ids($this->aValider($this->manager)));
        $this->assertTrue($this->show($this->manager, $deSaCommission)['body']['peutDecider']);
    }

    public function testLeGestionnaireNePeutPlusDeciderDepuisLeSite(): void
    {
        $sansValideur = $this->noteEnvoyee($this->adherent);
        $avecValideurs = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertFalse($this->show($this->manager, $sansValideur)['body']['peutDecider']);
        $this->assertFalse($this->show($this->manager, $avecValideurs)['body']['peutDecider']);
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
