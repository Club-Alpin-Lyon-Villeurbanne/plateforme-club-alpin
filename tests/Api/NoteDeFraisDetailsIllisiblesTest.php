<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Des `details` écrits en base hors de l'API (nombre non fini, texte à la place d'un nombre) ne
 * font échouer aucune lecture : une seule note illisible bloquerait toute la liste de compta-club,
 * qui vérifie aussi sa session par `HEAD /admin/notes-de-frais`.
 */
class NoteDeFraisDetailsIllisiblesTest extends ExpenseReportApiTestCase
{
    private ?ExpenseReport $noteDeSortie = null;

    protected function tearDown(): void
    {
        // Une note de sortie n'a pas de commission : le socle ne l'efface pas avec les autres.
        if (null !== $this->noteDeSortie) {
            $base = $this->em()->getConnection();
            $base->executeStatement('DELETE FROM expense_report_status_history WHERE expense_report_id = ?', [$this->noteDeSortie->getId()]);
            $base->executeStatement('DELETE FROM expense_report WHERE id = ?', [$this->noteDeSortie->getId()]);
        }

        parent::tearDown();
    }

    public function testLesListesSeLisentMalgreDesDetailsIllisibles(): void
    {
        $this->noteDeSortie = $this->createReport($this->adherent, ExpenseReportStatusEnum::SUBMITTED, '{"transport":{"type":"PERSONAL_VEHICLE","distance":"abc","tollFee":12},"accommodations":[{"price":"1e400"}],"others":"rien"}');
        $horsSortie = $this->creerAchat($this->adherent, ExpenseReportStatusEnum::REJECTED, '{"lignes":[{"id":"ligne-1","montant":1e400,"description":"Dégaines"},{"id":"ligne-2","montant":21.5,"description":"Cordelette"}]}', $this->commissionDeTest);

        $this->assertResponseStatus(200, $this->list($this->manager, '/api/admin/notes-de-frais'));
        $this->assertSame(200, $this->enTete($this->manager, '/api/admin/notes-de-frais'));
        $this->assertResponseStatus(200, $this->list($this->manager, '/api/admin/notes-de-frais', ['status' => 'rejected']));
        $this->assertResponseStatus(200, $this->list($this->adherent, '/api/notes-de-frais', ['inclure_brouillons' => 'true']));

        $lue = $this->show($this->adherent, $horsSortie);
        $this->assertResponseStatus(200, $lue);
        $this->assertEquals([0, 21.5], array_column($lue['body']['lignesAffichees'], 'montant'));
        $this->assertEquals(21.5, $lue['body']['totalDeclare']);

        $lue = $this->show($this->adherent, $this->noteDeSortie);
        $this->assertResponseStatus(200, $lue);
        // Le péage seul, partagé selon les taux des notes de sortie.
        $this->assertEqualsWithDelta(12 / $this->getContainer()->getParameter('expense_report')['divisionPeage'], $lue['body']['totalDeclare'], 0.01);
    }

    private function enTete(User $as, string $uri): int
    {
        $this->client->request('HEAD', $uri, [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwt($as),
        ]);

        return $this->client->getResponse()->getStatusCode();
    }
}
