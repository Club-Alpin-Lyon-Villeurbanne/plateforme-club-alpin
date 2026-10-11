<?php

namespace App\Repository;

use App\Entity\ExpenseReport;
use App\Entity\ExpenseReportStatusHistory;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExpenseReportStatusHistory>
 */
class ExpenseReportStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpenseReportStatusHistory::class);
    }

    public function dateDernierEnvoi(ExpenseReport $note): ?\DateTimeImmutable
    {
        $date = $this->createQueryBuilder('h')
            ->select('MAX(h.changedAt)')
            ->andWhere('h.expenseReport = :note')
            ->andWhere('h.newStatus = :envoyee')
            ->setParameter('note', $note)
            ->setParameter('envoyee', ExpenseReportStatusEnum::SUBMITTED)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $date ? null : new \DateTimeImmutable($date);
    }
}
