<?php

namespace App\Tests\Api;

use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Notes hors sortie dans la liste des notes de l'utilisateur : filtre par type. La liste de
 * compta-club : voir NoteHorsSortieComptaClubTest.
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
}
