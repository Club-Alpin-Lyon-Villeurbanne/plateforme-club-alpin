<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Liste « notes de frais à valider » : les notes hors sortie qu'un gestionnaire peut traiter,
 * envoyées par défaut, déjà traitées avec le filtre de statut.
 */
class NoteHorsSortieAValiderTest extends ExpenseReportApiTestCase
{
    public function testLeGestionnaireVoitLesNotesHorsSortieEnvoyeesDeTousLesDemandeurs(): void
    {
        $deLAdherent = $this->creerAchat($this->owner, ExpenseReportStatusEnum::SUBMITTED);
        $dUnAutre = $this->creerAchat($this->stranger, ExpenseReportStatusEnum::SUBMITTED);

        $response = $this->aValider($this->manager);

        $this->assertResponseStatus(200, $response);
        $this->assertContains($deLAdherent->getId(), $this->ids($response));
        $this->assertContains($dUnAutre->getId(), $this->ids($response));
    }

    public function testParDefautNiBrouillonNiNoteTraiteeNiNoteDeSortie(): void
    {
        $exclues = [
            $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED),
            $this->creerAchat($this->owner),
            $this->creerAchat($this->owner, ExpenseReportStatusEnum::APPROVED),
            $this->creerAchat($this->owner, ExpenseReportStatusEnum::REJECTED),
        ];
        $envoyee = $this->creerAchat($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $ids = $this->ids($this->aValider($this->manager));

        $this->assertContains($envoyee->getId(), $ids);
        foreach ($exclues as $note) {
            $this->assertNotContains($note->getId(), $ids);
        }
    }

    public function testLeFiltreDeStatutDonneLesDemandesDejaTraitees(): void
    {
        $sortieValidee = $this->createReport($this->owner, ExpenseReportStatusEnum::APPROVED);
        $validee = $this->creerAchat($this->owner, ExpenseReportStatusEnum::APPROVED);
        $aCorriger = $this->creerAchat($this->owner, ExpenseReportStatusEnum::REJECTED);
        $envoyee = $this->creerAchat($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $ids = $this->ids($this->aValider($this->manager, ['status' => ['approved', 'rejected']]));

        $this->assertContains($validee->getId(), $ids);
        $this->assertContains($aCorriger->getId(), $ids);
        $this->assertNotContains($envoyee->getId(), $ids);
        $this->assertNotContains($sortieValidee->getId(), $ids);
    }

    public function testUnBrouillonNEstJamaisAValider(): void
    {
        $brouillon = $this->creerAchat($this->owner);

        $ids = $this->ids($this->aValider($this->manager, ['status' => 'draft', 'inclure_brouillons' => 'true']));

        $this->assertNotContains($brouillon->getId(), $ids);
    }

    public function testLeGestionnaireNeVoitPasSesPropresNotesAValider(): void
    {
        $sienne = $this->creerAchat($this->manager, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertNotContains($sienne->getId(), $this->ids($this->aValider($this->manager)));
        $this->assertContains($sienne->getId(), $this->ids($this->aValider($this->otherManager)));
    }

    /**
     * @dataProvider filtresProvider
     */
    public function testUnTiersRecoitUneListeVide(array $filtres): void
    {
        $this->creerAchat($this->owner, ExpenseReportStatusEnum::SUBMITTED);
        $this->creerAchat($this->owner, ExpenseReportStatusEnum::APPROVED);

        $response = $this->aValider($this->stranger, $filtres);

        $this->assertResponseStatus(200, $response);
        $this->assertSame([], $response['body']['data']);
        $this->assertEquals(0, $response['body']['meta']['total']);
    }

    public static function filtresProvider(): iterable
    {
        yield 'sans filtre' => [[]];
        yield 'demandes déjà traitées' => [['status' => ['approved', 'rejected']]];
    }

    public function testLeGestionnaireVoitLeChoixEntreDonEtRemboursement(): void
    {
        $note = $this->noteEnvoyee($this->owner, false);

        $listee = $this->trouver($this->aValider($this->manager), $note);

        $this->assertArrayHasKey('refundRequired', $listee);
        $this->assertFalse($listee['refundRequired']);
    }

    public function testLaListeDonneCeQueLaPageAffiche(): void
    {
        $this->nommer($this->owner, 'Sam', 'Petit');
        $note = $this->noteEnvoyee($this->owner);

        $listee = $this->trouver($this->aValider($this->manager), $note);

        $this->assertSame('Dégaines pour la SAE', $listee['objet']);
        $this->assertSame('autre', $listee['type']);
        $this->assertSame($this->commissionVisible()->getTitle(), $listee['commission']['title']);
        $this->assertSame(['Sam', 'PETIT'], [$listee['utilisateur']['prenom'], $listee['utilisateur']['nom']]);
        $this->assertSame(86.4, $listee['totalDeclare']);
        $this->assertTrue($listee['peutDecider']);
    }

    private function aValider(User $as, array $filtres = []): array
    {
        return $this->list($as, '/api/notes-de-frais/a-valider', $filtres);
    }

    /**
     * Les notes laissées par les tests précédents restent en base : on regarde la première page,
     * où les plus récentes, celles du test, apparaissent.
     *
     * @return list<int>
     */
    private function ids(array $response): array
    {
        $this->assertResponseStatus(200, $response);

        return array_column($response['body']['data'], 'id');
    }

    private function trouver(array $response, ExpenseReport $note): array
    {
        $this->assertResponseStatus(200, $response);
        foreach ($response['body']['data'] as $listee) {
            if ($listee['id'] === $note->getId()) {
                return $listee;
            }
        }
        $this->fail(sprintf('La note %d n\'est pas dans la liste.', $note->getId()));
    }
}
