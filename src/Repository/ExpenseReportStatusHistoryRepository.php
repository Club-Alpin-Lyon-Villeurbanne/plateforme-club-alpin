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

    /**
     * Dernière validation ou dernier renvoi de la note, avec son auteur.
     */
    public function derniereDecision(ExpenseReport $note): ?ExpenseReportStatusHistory
    {
        return $this->createQueryBuilder('h')
            ->addSelect('auteur')
            ->join('h.changedBy', 'auteur')
            ->andWhere('h.expenseReport = :note')
            ->andWhere('h.newStatus IN (:decisions)')
            ->setParameter('note', $note)
            ->setParameter('decisions', [ExpenseReportStatusEnum::APPROVED->value, ExpenseReportStatusEnum::REJECTED->value])
            // Deux changements dans la même seconde : l'ordre d'insertion départage.
            ->orderBy('h.changedAt', 'DESC')
            ->addOrderBy('h.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
