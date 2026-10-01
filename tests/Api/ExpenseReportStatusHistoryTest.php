<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Entity\ExpenseReportStatusHistory;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Historique des changements de statut : une ligne par changement effectif, avec son auteur.
 */
class ExpenseReportStatusHistoryTest extends ExpenseReportApiTestCase
{
    public function testEachStatusChangeIsRecordedWithItsAuthor(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertResponseStatus(200, $this->patch($this->manager, $report, ['status' => 'approved']));
        $this->assertResponseStatus(200, $this->patch($this->manager, $report, ['status' => 'accounted']));

        $this->assertSame([
            ['submitted', 'approved', $this->manager->getId()],
            ['approved', 'accounted', $this->manager->getId()],
        ], $this->history($report));
    }

    public function testSubmissionIsRecordedWithTheOwner(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $this->assertResponseStatus(200, $this->patch($this->owner, $report, ['details' => self::DETAILS, 'refundRequired' => true, 'status' => 'submitted']));

        $this->assertSame([['draft', 'submitted', $this->owner->getId()]], $this->history($report));
    }

    public function testNothingIsRecordedWithoutStatusChange(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $this->assertResponseStatus(200, $this->patch($this->owner, $report, ['refundRequired' => false]));

        $this->assertSame([], $this->history($report));
    }

    public function testNothingIsRecordedForARefusedRequest(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertRefused($this->patch($this->owner, $report, ['status' => 'approved']), 'status');

        $this->assertSame([], $this->history($report));
    }

    /**
     * @return list<array{0: ?string, 1: string, 2: ?int}> [ancien statut, nouveau statut, auteur]
     */
    private function history(ExpenseReport $report): array
    {
        $em = $this->em();
        $em->clear();
        $rows = $em->getRepository(ExpenseReportStatusHistory::class)->findBy(['expenseReport' => $report->getId()], ['id' => 'ASC']);

        return array_map(static fn (ExpenseReportStatusHistory $row) => [
            $row->getOldStatus()?->value,
            $row->getNewStatus()->value,
            $row->getChangedBy()?->getId(),
        ], $rows);
    }
}
