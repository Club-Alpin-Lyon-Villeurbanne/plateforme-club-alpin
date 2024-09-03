<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DetailsImmutabilityValidator
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate(ExpenseReport $expenseReport, ExecutionContextInterface $context)
    {
        $oldStatus = $this->getOldStatus($expenseReport);

        if (ExpenseReportStatusEnum::DRAFT !== $oldStatus && $this->detailsHaveChanged($expenseReport)) {
            $context->buildViolation('Details cannot be modified once the expense report is submitted.')
                ->atPath('details')
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

    private function detailsHaveChanged(ExpenseReport $expenseReport): bool
    {
        $originalEntity = $this->entityManager->getUnitOfWork()->getOriginalEntityData($expenseReport);
        $originalDetails = $originalEntity['details'] ?? [];

        return $originalDetails !== $expenseReport->getDetails();
    }
}
