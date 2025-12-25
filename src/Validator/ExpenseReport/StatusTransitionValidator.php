<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class StatusTransitionValidator
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate(ExpenseReport $expenseReport, ExecutionContextInterface $context)
    {
        $oldStatus = $this->getOldStatus($expenseReport);
        $newStatus = $expenseReport->getStatus();

        if ($oldStatus === $newStatus) {
            return;
        }

        $validTransitions = [
            ExpenseReportStatusEnum::DRAFT->value => [ExpenseReportStatusEnum::SUBMITTED->value],
            ExpenseReportStatusEnum::SUBMITTED->value => [ExpenseReportStatusEnum::APPROVED->value, ExpenseReportStatusEnum::REJECTED->value],
            ExpenseReportStatusEnum::APPROVED->value => [ExpenseReportStatusEnum::ACCOUNTED->value],
            ExpenseReportStatusEnum::REJECTED->value => [ExpenseReportStatusEnum::SUBMITTED->value],
            // ACCOUNTED is a terminal state
        ];

        if (!isset($validTransitions[$oldStatus->value]) || !\in_array($newStatus->value, $validTransitions[$oldStatus->value], true)) {
            $context->buildViolation('Invalid status transition from "{{ oldStatus }}" to "{{ newStatus }}".')
                ->setParameter('{{ oldStatus }}', $oldStatus->value)
                ->setParameter('{{ newStatus }}', $newStatus->value)
                ->atPath('status')
                ->addViolation();
        }
    }

    private function getOldStatus(ExpenseReport $expenseReport): ExpenseReportStatusEnum
    {
        if (null === $expenseReport->getId()) {
            return ExpenseReportStatusEnum::DRAFT;
        }

        $originalEntity = $this->entityManager->getUnitOfWork()->getOriginalEntityData($expenseReport);

        return $originalEntity['status'] ?? ExpenseReportStatusEnum::DRAFT;
    }
}
