<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Entity\UserAttr;

/**
 * Commissions où la page de demande propose une formation : celles où le demandeur a un rôle
 * d'encadrement, selon la règle du contrôle de création.
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

    /**
     * @return array<int, array{id: int, titre: string, formationPossible: bool}> par identifiant
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
