<?php

namespace App\Repository;

use App\Entity\ExpenseAttachment;
use App\Entity\ExpenseReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExpenseAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpenseAttachment::class);
    }

    /**
     * @return list<ExpenseAttachment>
     */
    public function findByExpenseReportAndExpenseId(ExpenseReport $expenseReport, string $expenseId): array
    {
        return $this->findBy(['expenseReport' => $expenseReport, 'expenseId' => $expenseId], ['id' => 'ASC']);
    }
}
