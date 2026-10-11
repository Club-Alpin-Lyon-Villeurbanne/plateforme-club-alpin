<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * « À valider » d'un gestionnaire en deux files : les notes qui l'attendent (`attente=moi` : sans
 * valideur, ou de ses commissions s'il est aussi responsable), que compte le menu, et celles qui
 * attendent un responsable (`attente=responsables`), qu'il peut traiter à leur place.
 */
class NoteHorsSortieTresorerieAValiderTest extends ExpenseReportApiTestCase
{
    public function testPourLaTresorerieSeulesLesNotesSansValideur(): void
    {
        $sansResponsable = $this->noteEnvoyee($this->adherent);
        $seulResponsableDemandeur = $this->noteEnvoyee($this->responsableAutreCommission, commission: $this->autreCommission);
        $chezLesResponsables = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $ids = $this->ids($this->aValider($this->manager, 'moi'));

        $this->assertContains($sansResponsable->getId(), $ids);
        $this->assertContains($seulResponsableDemandeur->getId(), $ids);
        $this->assertNotContains($chezLesResponsables->getId(), $ids);
    }

    public function testUneCommissionDontLeResponsableEstInactifVaALaTresorerie(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->autreCommission);
        $this->assertNotContains($note->getId(), $this->ids($this->aValider($this->manager, 'moi')));

        $this->em()->find(User::class, $this->responsableAutreCommission->getId())->setDoitRenouveler(true);
        $this->em()->flush();

        $this->assertContains($note->getId(), $this->ids($this->aValider($this->manager, 'moi')));
    }

    public function testChezLesResponsablesLesNotesQuiOntDesValideurs(): void
    {
        $sansResponsable = $this->noteEnvoyee($this->adherent);
        $chezLesResponsables = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $response = $this->aValider($this->manager, 'responsables');

        $this->assertNotContains($sansResponsable->getId(), $this->ids($response));
        $listee = $this->trouver($response, $chezLesResponsables);
        $this->assertSame([['Alex', 'DURAND'], ['Sam', 'PETIT']], array_map(static fn (array $valideur) => [$valideur['prenom'], $valideur['nom']], $listee['valideurs']));
        $this->assertTrue($listee['peutDecider']);
    }

    public function testUnGestionnaireResponsableTrouveLesNotesDeSesCommissionsDansSaFile(): void
    {
        $this->rendreResponsable($this->manager, $this->commissionDeTest);
        $deSaCommission = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $dUneAutre = $this->noteEnvoyee($this->adherent, commission: $this->autreCommission);

        $pourLui = $this->ids($this->aValider($this->manager, 'moi'));
        $chezLesResponsables = $this->ids($this->aValider($this->manager, 'responsables'));

        $this->assertContains($deSaCommission->getId(), $pourLui);
        $this->assertNotContains($deSaCommission->getId(), $chezLesResponsables);
        $this->assertContains($dUneAutre->getId(), $chezLesResponsables);
        $this->assertNotContains($dUneAutre->getId(), $pourLui);
    }

    public function testLesDeuxFilesFontLaListeComplete(): void
    {
        $sansResponsable = $this->noteEnvoyee($this->adherent);
        $chezLesResponsables = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $ids = $this->ids($this->aValider($this->manager));

        $this->assertContains($sansResponsable->getId(), $ids);
        $this->assertContains($chezLesResponsables->getId(), $ids);
    }

    public function testLesFilesNeMontrentQueLesNotesEnvoyeesSaufFiltreDeStatut(): void
    {
        $validee = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::APPROVED, null, $this->commissionDeTest);

        $this->assertNotContains($validee->getId(), $this->ids($this->aValider($this->manager, 'responsables')));
        $this->assertContains($validee->getId(), $this->ids($this->aValider($this->manager, 'responsables', ['status' => ['approved']])));
    }

    public function testLeResponsableNAQueSaFile(): void
    {
        $note = $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);
        $this->noteEnvoyee($this->adherent);

        $this->assertSame([$note->getId()], $this->ids($this->aValider($this->responsable, 'moi')));
        $this->assertSame([], $this->ids($this->aValider($this->responsable, 'responsables')));
    }

    /**
     * @dataProvider attentesProvider
     */
    public function testLeTiersNAAucuneFile(string $attente): void
    {
        $this->noteEnvoyee($this->adherent);
        $this->noteEnvoyee($this->adherent, commission: $this->commissionDeTest);

        $this->assertSame([], $this->ids($this->aValider($this->stranger, $attente)));
    }

    public static function attentesProvider(): iterable
    {
        yield 'la sienne' => ['moi'];
        yield 'chez les responsables' => ['responsables'];
    }

    private function aValider(User $as, ?string $attente = null, array $filtres = []): array
    {
        return $this->list($as, '/api/notes-de-frais/a-valider', array_merge(null === $attente ? [] : ['attente' => $attente], $filtres));
    }

    /**
     * Les notes laissées par les autres tests restent en base : seule la première page compte,
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
        foreach ($response['body']['data'] as $listee) {
            if ($listee['id'] === $note->getId()) {
                return $listee;
            }
        }
        $this->fail(sprintf('La note %d n\'est pas dans la liste.', $note->getId()));
    }
}
