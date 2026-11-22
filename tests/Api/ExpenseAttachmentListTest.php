<?php

namespace App\Tests\Api;

use App\Entity\ExpenseAttachment;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Liste des justificatifs (GET /api/notes-de-frais/{id}/pieces-jointes) : propriétaire et gestionnaires seulement.
 */
class ExpenseAttachmentListTest extends ExpenseReportApiTestCase
{
    public function testOwnerAndManagerSeeTheAttachments(): void
    {
        $report = $this->reportWithAttachment();

        $this->assertCount(1, $this->listAttachments($this->owner, $report)['body']['data']);
        $this->assertCount(1, $this->listAttachments($this->manager, $report)['body']['data']);
    }

    public function testSomeoneElseDoesNotSeeTheAttachments(): void
    {
        $report = $this->reportWithAttachment();

        $response = $this->listAttachments($this->stranger, $report);

        $this->assertResponseStatus(200, $response);
        $this->assertSame([], $response['body']['data']);
    }

    private function reportWithAttachment(): ExpenseReport
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);
        $em = $this->em();

        $attachment = new ExpenseAttachment();
        $attachment->setUser($em->find(User::class, $this->owner->getId()));
        $attachment->setExpenseReport($em->find(ExpenseReport::class, $report->getId()));
        $attachment->setExpenseId('transport.ticketPrice');
        $attachment->setFileName('billet.png');
        $attachment->setFilePath('/tmp/billet.png');
        $em->persist($attachment);
        $em->flush();
        $em->clear();

        return $report;
    }
}
