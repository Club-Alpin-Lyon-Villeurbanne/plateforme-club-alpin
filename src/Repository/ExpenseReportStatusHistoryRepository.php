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
        return $this->dateDEnvoi($note, 'MAX');
    }

    public function datePremierEnvoi(ExpenseReport $note): ?\DateTimeImmutable
    {
        return $this->dateDEnvoi($note, 'MIN');
    }

    /**
     * Dernière décision sur la note, avec son auteur : pré-validation par un responsable,
     * validation ou renvoi. La comptabilisation n'en est pas une.
     */
    public function derniereDecision(ExpenseReport $note): ?ExpenseReportStatusHistory
    {
        return $this->dernierPassage($note, ExpenseReportStatusEnum::PREAPPROVED, ExpenseReportStatusEnum::APPROVED, ExpenseReportStatusEnum::REJECTED);
    }

    /**
     * Dernière validation de la note par un responsable, avec son auteur, même si la trésorerie a
     * décidé ensuite.
     */
    public function dernierePreValidation(ExpenseReport $note): ?ExpenseReportStatusHistory
    {
        return $this->dernierPassage($note, ExpenseReportStatusEnum::PREAPPROVED);
    }

    private function dernierPassage(ExpenseReport $note, ExpenseReportStatusEnum ...$statuts): ?ExpenseReportStatusHistory
    {
        return $this->createQueryBuilder('h')
            ->addSelect('auteur')
            ->join('h.changedBy', 'auteur')
            ->andWhere('h.expenseReport = :note')
            ->andWhere('h.newStatus IN (:statuts)')
            ->setParameter('note', $note)
            ->setParameter('statuts', array_map(static fn (ExpenseReportStatusEnum $statut) => $statut->value, $statuts))
            // Deux changements dans la même seconde : l'ordre d'insertion départage.
            ->orderBy('h.changedAt', 'DESC')
            ->addOrderBy('h.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param 'MIN'|'MAX' $agregat
     */
    private function dateDEnvoi(ExpenseReport $note, string $agregat): ?\DateTimeImmutable
    {
        $date = $this->createQueryBuilder('h')
            ->select(sprintf('%s(h.changedAt)', $agregat))
            ->andWhere('h.expenseReport = :note')
            ->andWhere('h.newStatus = :envoyee')
            ->setParameter('note', $note)
            ->setParameter('envoyee', ExpenseReportStatusEnum::SUBMITTED)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $date ? null : new \DateTimeImmutable($date);
    }
}
