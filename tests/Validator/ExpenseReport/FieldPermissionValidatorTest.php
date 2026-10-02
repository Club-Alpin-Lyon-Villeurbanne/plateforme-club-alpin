<?php

namespace App\Tests\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Validator\ExpenseReport\ExpenseReportOriginalState;
use App\Validator\ExpenseReport\FieldPermissionValidator;
use PHPUnit\Framework\TestCase;

class FieldPermissionValidatorTest extends TestCase
{
    use ExpenseReportValidationTrait;

    private const ORIGINAL = [
        'details' => '{"transport":{"type":"PUBLIC_TRANSPORT","ticketPrice":10},"accommodations":[],"others":[]}',
        'refundRequired' => true,
        'statusComment' => 'Commentaire du gestionnaire',
    ];

    /**
     * details et refundRequired : uniquement le propriétaire, uniquement en brouillon ou rejetée.
     */
    private const CLAIM_EDITORS = ['propriétaire', 'gestionnaire propriétaire'];
    private const CLAIM_EDITABLE_STATUSES = [ExpenseReportStatusEnum::DRAFT, ExpenseReportStatusEnum::REJECTED];

    /**
     * @dataProvider claimFieldProvider
     */
    public function testClaimCanOnlyBeChangedByTheOwnerBeforeSubmission(string $field, string $actor, ExpenseReportStatusEnum $oldStatus, bool $allowed): void
    {
        $report = $this->report($oldStatus);
        match ($field) {
            'details' => $report->setDetails('{"transport":{"type":"PUBLIC_TRANSPORT","ticketPrice":9999},"accommodations":[],"others":[]}'),
            'refundRequired' => $report->setRefundRequired(false),
        };

        $this->assertSame($allowed ? [] : [$field], $this->refusedFields($actor, $oldStatus, $report));
    }

    public static function claimFieldProvider(): iterable
    {
        foreach (['details', 'refundRequired'] as $field) {
            foreach (self::actors() as $actor) {
                foreach (ExpenseReportStatusEnum::cases() as $oldStatus) {
                    $allowed = \in_array($actor, self::CLAIM_EDITORS, true) && \in_array($oldStatus, self::CLAIM_EDITABLE_STATUSES, true);
                    yield sprintf('%s modifié par %s, statut d\'origine %s', $field, $actor, $oldStatus->value) => [$field, $actor, $oldStatus, $allowed];
                }
            }
        }
    }

    /**
     * @dataProvider statusCommentProvider
     */
    public function testStatusCommentCanOnlyBeSetByAManagerWhenChangingTheStatus(string $actor, ExpenseReportStatusEnum $oldStatus, ExpenseReportStatusEnum $newStatus, bool $allowed): void
    {
        $report = $this->report($newStatus);
        $report->setStatusComment('Nouveau commentaire');

        $this->assertSame($allowed ? [] : ['statusComment'], $this->refusedFields($actor, $oldStatus, $report));
    }

    public static function statusCommentProvider(): iterable
    {
        $cases = [
            'submitted → rejected' => [ExpenseReportStatusEnum::SUBMITTED, ExpenseReportStatusEnum::REJECTED],
            'submitted → approved' => [ExpenseReportStatusEnum::SUBMITTED, ExpenseReportStatusEnum::APPROVED],
            'approved → accounted' => [ExpenseReportStatusEnum::APPROVED, ExpenseReportStatusEnum::ACCOUNTED],
            'submitted inchangé' => [ExpenseReportStatusEnum::SUBMITTED, ExpenseReportStatusEnum::SUBMITTED],
            'accounted inchangé' => [ExpenseReportStatusEnum::ACCOUNTED, ExpenseReportStatusEnum::ACCOUNTED],
        ];
        foreach (self::actors() as $actor) {
            foreach ($cases as $label => [$old, $new]) {
                $allowed = 'gestionnaire' === $actor && $old !== $new;
                yield sprintf('commentaire par %s, %s', $actor, $label) => [$actor, $old, $new, $allowed];
            }
        }
    }

    /**
     * @dataProvider unchangedProvider
     */
    public function testResendingStoredValuesIsAlwaysAccepted(string $actor, ExpenseReportStatusEnum $oldStatus): void
    {
        $this->assertSame([], $this->refusedFields($actor, $oldStatus, $this->report($oldStatus)));
    }

    public static function unchangedProvider(): iterable
    {
        foreach (self::actors() as $actor) {
            foreach (ExpenseReportStatusEnum::cases() as $oldStatus) {
                yield sprintf('%s renvoie les valeurs enregistrées, statut %s', $actor, $oldStatus->value) => [$actor, $oldStatus];
            }
        }
    }

    /**
     * Note existante dont les valeurs sont identiques à celles enregistrées.
     */
    private function report(ExpenseReportStatusEnum $status): ExpenseReport
    {
        $report = $this->existingReport();
        $report->setStatus($status);
        $report->setDetails(self::ORIGINAL['details']);
        $report->setRefundRequired(self::ORIGINAL['refundRequired']);
        $report->setStatusComment(self::ORIGINAL['statusComment']);

        return $report;
    }

    /**
     * @return list<string>
     */
    private function refusedFields(string $actor, ExpenseReportStatusEnum $oldStatus, ExpenseReport $report): array
    {
        $originalState = $this->createMock(ExpenseReportOriginalState::class);
        $originalState->method('value')->willReturnCallback(static fn (ExpenseReport $r, string $field) => self::ORIGINAL[$field] ?? null);

        $context = $this->context($report);
        (new FieldPermissionValidator($originalState, $this->actor($actor)))->validate($report, $oldStatus, $context);

        return $this->violationPaths($context);
    }
}
