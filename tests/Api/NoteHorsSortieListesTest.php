<?php

namespace App\Tests\Api;

use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Notes hors sortie dans les listes : filtre par type des notes de l'utilisateur, et liste des
 * gestionnaires (relayée par compta-club) limitée aux notes de sortie.
 */
class NoteHorsSortieListesTest extends ExpenseReportApiTestCase
{
    public function testLesNotesDeLUtilisateurSeFiltrentParType(): void
    {
        $noteDeSortie = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);
        $achat = $this->creerAchat($this->owner);

        $achats = $this->list($this->owner, '/api/notes-de-frais', ['type' => 'autre', 'inclure_brouillons' => 'true']);
        $notesDeSortie = $this->list($this->owner, '/api/notes-de-frais', ['type' => 'sortie', 'inclure_brouillons' => 'true']);

        $this->assertSame([$achat->getId()], array_column($achats['body']['data'], 'id'));
        $this->assertSame([$noteDeSortie->getId()], array_column($notesDeSortie['body']['data'], 'id'));
    }

    public function testLaListeDesGestionnairesNeRenvoieAucuneNoteHorsSortie(): void
    {
        $noteDeSortie = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);
        $achat = $this->creerAchat($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $response = $this->list($this->manager, '/api/admin/notes-de-frais');

        $this->assertResponseStatus(200, $response);
        $ids = array_column($response['body']['data'], 'id');
        $this->assertContains($noteDeSortie->getId(), $ids);
        $this->assertNotContains($achat->getId(), $ids);
        $this->assertSame(['sortie'], array_values(array_unique(array_column($response['body']['data'], 'type'))));
        $this->assertSame([], $this->list($this->manager, '/api/admin/notes-de-frais', ['type' => 'autre'])['body']['data']);
    }
}
