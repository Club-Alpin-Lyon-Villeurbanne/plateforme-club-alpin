<?php

namespace App\Repository;

use App\Entity\Attachment;
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

    public function findByExpenseReportAndExpenseId(ExpenseReport $expenseReport, string $expenseId): ?ExpenseAttachment
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.expenseReport = :expenseReport')
            ->andWhere('a.expenseId = :expenseId')
            ->setParameter('expenseReport', $expenseReport)
            ->setParameter('expenseId', $expenseId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}