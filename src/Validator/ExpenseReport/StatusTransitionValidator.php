<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Security\ExpenseReportActor;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class StatusTransitionValidator
{
    /**
     * Le propriétaire ne peut que soumettre (ou resoumettre après un rejet).
     */
    private const OWNER_TRANSITIONS = [
        ExpenseReportStatusEnum::DRAFT->value => [ExpenseReportStatusEnum::SUBMITTED],
        ExpenseReportStatusEnum::REJECTED->value => [ExpenseReportStatusEnum::SUBMITTED],
    ];

    /**
     * Un gestionnaire, sur la note d'un autre, décide puis comptabilise.
     * ACCOUNTED est un état terminal.
     */
    private const MANAGER_TRANSITIONS = [
        ExpenseReportStatusEnum::SUBMITTED->value => [ExpenseReportStatusEnum::APPROVED, ExpenseReportStatusEnum::REJECTED],
        ExpenseReportStatusEnum::APPROVED->value => [ExpenseReportStatusEnum::ACCOUNTED],
    ];

    public function __construct(
        private readonly ExpenseReportActor $actor,
    ) {
    }

    public function validate(ExpenseReport $expenseReport, ExpenseReportStatusEnum $oldStatus, ExecutionContextInterface $context): void
    {
        $newStatus = $expenseReport->getStatus();

        if ($oldStatus === $newStatus) {
            return;
        }

        if (!\in_array($newStatus, $this->allowedTargets($expenseReport, $oldStatus), true)) {
            $context->buildViolation('Invalid status transition from "{{ oldStatus }}" to "{{ newStatus }}".')
                ->setParameter('{{ oldStatus }}', $oldStatus->value)
                ->setParameter('{{ newStatus }}', $newStatus->value)
                ->atPath('status')
                ->addViolation();
        }
    }

    /**
     * @return list<ExpenseReportStatusEnum>
     */
    private function allowedTargets(ExpenseReport $expenseReport, ExpenseReportStatusEnum $oldStatus): array
    {
        if ($this->actor->isManagerOf($expenseReport)) {
            return self::MANAGER_TRANSITIONS[$oldStatus->value] ?? [];
        }

        if ($this->actor->isOwnerOf($expenseReport)) {
            return self::OWNER_TRANSITIONS[$oldStatus->value] ?? [];
        }

        return [];
    }
}
