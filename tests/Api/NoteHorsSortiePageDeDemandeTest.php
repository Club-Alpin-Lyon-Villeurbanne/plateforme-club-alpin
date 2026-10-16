<?php

namespace App\Tests\Api;

use App\Entity\Evt;
use App\Entity\User;
use App\Service\NoteDeFrais\ControlesNoteHorsSortie;

/**
 * Page de demande : réservée aux rôles d'encadrement, sauf pour reprendre une note commencée.
 * Elle dit à qui partira la note, et où se fait une note de sortie.
 */
class NoteHorsSortiePageDeDemandeTest extends ExpenseReportApiTestCase
{
    public function testSansRoleLaPageExpliqueAQuiLesNotesSontReservees(): void
    {
        $this->signin($this->adherent);
        $page = $this->client->request('GET', '/notes-de-frais/nouvelle');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $page->filter('#notes-de-frais'));
        $this->assertStringContainsString(ControlesNoteHorsSortie::RESERVEES_A_L_ENCADREMENT, $page->filter('.main-type')->text());
    }

    public function testSansRoleUneNoteCommenceeSeReprend(): void
    {
        $this->signin($this->adherent);
        $note = $this->creerAchat($this->adherent);
        $page = $this->client->request('GET', '/notes-de-frais/nouvelle?id=' . $note->getId());

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $page->filter('#notes-de-frais'));
    }

    public function testLaPageDitOuSeFaitUneNoteDeSortie(): void
    {
        $sortie = $this->donnees($this->encadrant)['sortie'];

        $this->assertSame(Evt::EXPENSE_REPORT_DEADLINE_DAYS, $sortie['delaiJours']);
        $this->assertSame('/profil/sorties/prev', $sortie['sortiesPassees']);
        $this->assertStringStartsWith('https://forms.clickup.com/', $sortie['signalement']);
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

    public function testUnEncadrantNEstResponsableDAucuneCommission(): void
    {
        $this->assertSame([], array_filter(array_column($this->commissionsProposees($this->encadrant), 'demandeurResponsable')));
    }

    /**
     * @return array<int, array{id: int, titre: string, valideurs: list<array<string, mixed>>, demandeurResponsable: bool}> par identifiant
     */
    private function commissionsProposees(User $demandeur): array
    {
        return array_column($this->donnees($demandeur)['commissions'], null, 'id');
    }

    private function donnees(User $demandeur): array
    {
        $this->signin($demandeur);
        $page = $this->client->request('GET', '/notes-de-frais/nouvelle');
        $this->assertResponseIsSuccessful();

        return json_decode($page->filter('#notes-de-frais')->attr('data-donnees'), true);
    }
}
