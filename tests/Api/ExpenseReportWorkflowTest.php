<?php

namespace App\Tests\Api;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;

/**
 * Circuit de validation des notes de frais via PATCH /api/notes-de-frais/{id} :
 * qui peut changer quel statut, et quels champs.
 */
class ExpenseReportWorkflowTest extends ExpenseReportApiTestCase
{
    // —— Propriétaire ——

    public function testOwnerCannotApproveOwnReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertRefused($this->patch($this->owner, $report, ['status' => 'approved']), 'status');
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($report)->getStatus());
    }

    public function testOwnerCannotAccountOwnReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::APPROVED);

        $this->assertRefused($this->patch($this->owner, $report, ['status' => 'accounted']), 'status');
    }

    public function testOwnerCannotSendAccountedReportBackToDraft(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::ACCOUNTED);

        $this->assertRefused($this->patch($this->owner, $report, ['status' => 'draft']), 'status');
    }

    public function testOwnerCannotWithdrawSubmittedReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertRefused($this->patch($this->owner, $report, ['status' => 'draft']), 'status');
    }

    public function testOwnerCannotChangeDetailsOfAccountedReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::ACCOUNTED);

        $this->assertRefused($this->patch($this->owner, $report, ['details' => self::INFLATED_DETAILS]), 'details');
        $this->assertSame(self::DETAILS, $this->reload($report)->getDetails());
    }

    public function testOwnerCannotChangeRefundChoiceOfApprovedReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::APPROVED);

        $this->assertRefused($this->patch($this->owner, $report, ['refundRequired' => false]), 'refundRequired');
    }

    public function testOwnerCannotWriteStatusComment(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertRefused($this->patch($this->owner, $report, ['commentaireStatut' => 'OK trésorier']), 'commentaireStatut');
    }

    public function testIdAndCreationDateAreIgnored(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);
        $createdAt = $this->reload($report)->getCreatedAt();

        $response = $this->patch($this->owner, $report, ['id' => 999999, 'dateCreation' => '2030-01-01T00:00:00+00:00']);

        $this->assertResponseStatus(200, $response);
        $this->assertEquals($createdAt, $this->reload($report)->getCreatedAt());
    }

    // —— Gestionnaires ——

    public function testManagerApprovesThenAccountsSomeoneElsesReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertResponseStatus(200, $this->patch($this->manager, $report, ['status' => 'approved']));
        $this->assertResponseStatus(200, $this->patch($this->manager, $report, ['status' => 'accounted']));
        $this->assertSame(ExpenseReportStatusEnum::ACCOUNTED, $this->reload($report)->getStatus());
    }

    public function testManagerRejectsWithComment(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $response = $this->patch($this->manager, $report, ['status' => 'rejected', 'commentaireStatut' => 'Justificatif manquant']);

        $this->assertResponseStatus(200, $response);
        $reloaded = $this->reload($report);
        $this->assertSame(ExpenseReportStatusEnum::REJECTED, $reloaded->getStatus());
        $this->assertSame('Justificatif manquant', $reloaded->getStatusComment());
    }

    public function testManagerCommentWithForbiddenTransitionIsNotSaved(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $response = $this->patch($this->manager, $report, ['status' => 'accounted', 'commentaireStatut' => 'Payé']);

        $this->assertRefused($response, 'status');
        $this->assertNull($this->reload($report)->getStatusComment());
    }

    public function testManagerCannotChangeAmounts(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertRefused($this->patch($this->manager, $report, ['details' => self::INFLATED_DETAILS]), 'details');
        $this->assertRefused($this->patch($this->manager, $report, ['refundRequired' => false]), 'refundRequired');
    }

    public function testManagerCannotApproveOwnReportButAnotherManagerCan(): void
    {
        $report = $this->createReport($this->manager, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertRefused($this->patch($this->manager, $report, ['status' => 'approved']), 'status');
        $this->assertResponseStatus(200, $this->patch($this->otherManager, $report, ['status' => 'approved']));
    }

    // —— Tiers et requêtes atypiques ——

    public function testStrangerIsForbidden(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::SUBMITTED);

        $this->assertResponseStatus(403, $this->patch($this->stranger, $report, ['status' => 'approved']));
        $this->assertResponseStatus(403, $this->patch($this->stranger, $report, ['details' => self::INFLATED_DETAILS]));
    }

    public function testEmptyPatchOnAccountedReportChangesNothing(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::ACCOUNTED);

        $this->assertResponseStatus(200, $this->patch($this->owner, $report, []));
        $this->assertSame(ExpenseReportStatusEnum::ACCOUNTED, $this->reload($report)->getStatus());
    }

    public function testNullValuesAreRejected(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT);

        $this->assertResponseStatus(400, $this->patch($this->owner, $report, ['status' => null]));
        $this->assertResponseStatus(400, $this->patch($this->owner, $report, ['refundRequired' => null]));
    }

    // —— Non-régression du front adhérent ——

    public function testOwnerSavesDraftThenSubmits(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::DRAFT, '{"transport":{"type":"PUBLIC_TRANSPORT"},"accommodations":[],"others":[]}');

        $this->assertResponseStatus(200, $this->patch($this->owner, $report, ['details' => self::DETAILS, 'refundRequired' => true]));
        $this->assertResponseStatus(200, $this->patch($this->owner, $report, ['details' => self::DETAILS, 'refundRequired' => true, 'status' => 'submitted']));
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $this->reload($report)->getStatus());
    }

    public function testOwnerFixesAndResubmitsRejectedReport(): void
    {
        $report = $this->createReport($this->owner, ExpenseReportStatusEnum::REJECTED);
        $this->setStatusComment($report, 'Justificatif manquant');
        $fixed = '{"transport":{"type":"PERSONAL_VEHICLE","distance":120,"tollFee":0},"accommodations":[],"others":[]}';

        // Le front renvoie la note telle qu'il l'a reçue, commentaire du gestionnaire compris.
        $response = $this->patch($this->owner, $report, [
            'details' => $fixed,
            'refundRequired' => false,
            'commentaireStatut' => 'Justificatif manquant',
            'status' => 'submitted',
        ]);

        $this->assertResponseStatus(200, $response);
        $reloaded = $this->reload($report);
        $this->assertSame(ExpenseReportStatusEnum::SUBMITTED, $reloaded->getStatus());
        $this->assertSame($fixed, $reloaded->getDetails());
    }

    private function setStatusComment(ExpenseReport $report, string $comment): void
    {
        $em = $this->em();
        $em->find(ExpenseReport::class, $report->getId())->setStatusComment($comment);
        $em->flush();
        $em->clear();
    }
}
