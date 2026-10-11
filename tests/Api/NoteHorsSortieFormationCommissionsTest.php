<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Entity\UserAttr;

/**
 * Commissions où la page de demande propose une formation : celles où le demandeur a un rôle
 * d'encadrement, selon la règle du contrôle de création. Et celles dont il est responsable, pour
 * lui dire pourquoi la trésorerie validera.
 */
class NoteHorsSortieFormationCommissionsTest extends ExpenseReportApiTestCase
{
    public function testLaPageProposeLaFormationSurLesCommissionsQueLeDemandeurEncadre(): void
    {
        $commissions = $this->commissionsProposees($this->encadrant);

        $this->assertTrue($commissions[$this->commissionDeTest->getId()]['formationPossible']);
        $this->assertFalse($commissions[$this->autreCommission->getId()]['formationPossible']);
    }

    public function testChaqueRoleDEncadrementOuvreLaFormation(): void
    {
        $this->donnerUnRole($this->adherent, UserAttr::BENEVOLE, $this->autreCommission);

        $commissions = $this->commissionsProposees($this->adherent);

        $this->assertTrue($commissions[$this->autreCommission->getId()]['formationPossible']);
        $this->assertFalse($commissions[$this->commissionDeTest->getId()]['formationPossible']);
    }

    public function testSansRoleDEncadrementAucuneCommissionNePermetDeFormation(): void
    {
        $commissions = $this->commissionsProposees($this->adherent);

        $this->assertNotEmpty($commissions);
        $this->assertSame([], array_filter(array_column($commissions, 'formationPossible')));
    }

    public function testLaPageSaitQuandLeDemandeurEstLeSeulResponsable(): void
    {
        $commissions = $this->commissionsProposees($this->responsableAutreCommission);

        $this->assertTrue($commissions[$this->autreCommission->getId()]['demandeurResponsable']);
        $this->assertSame([], $commissions[$this->autreCommission->getId()]['valideurs']);
        $this->assertFalse($commissions[$this->commissionDeTest->getId()]['demandeurResponsable']);
    }

    public function testUnResponsableAvecUnCoResponsableEstValidePar(): void
    {
        $commission = $this->commissionsProposees($this->responsable)[$this->commissionDeTest->getId()];

        $this->assertTrue($commission['demandeurResponsable']);
        $this->assertSame(['Sam'], array_column($commission['valideurs'], 'prenom'));
    }

    public function testUnAdherentSansRoleNEstResponsableDAucuneCommission(): void
    {
        $this->assertSame([], array_filter(array_column($this->commissionsProposees($this->adherent), 'demandeurResponsable')));
    }

    /**
     * @return array<int, array{id: int, titre: string, valideurs: list<array<string, mixed>>, formationPossible: bool, demandeurResponsable: bool}> par identifiant
     */
    private function commissionsProposees(User $demandeur): array
    {
        $this->signin($demandeur);
        $page = $this->client->request('GET', '/notes-de-frais/nouvelle');
        $this->assertResponseIsSuccessful();

        $donnees = json_decode($page->filter('#notes-de-frais')->attr('data-donnees'), true);

        return array_column($donnees['commissions'], null, 'id');
    }
}
