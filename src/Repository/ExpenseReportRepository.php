<?php

namespace App\Repository;

use App\Entity\ExpenseReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExpenseReport>
 *
 * @method ExpenseReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpenseReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpenseReport[]    findAll()
 * @method ExpenseReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpenseReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpenseReport::class);
    }

    public function getExpenseReportByEventAndUser(int $eventId, int $userId): ?ExpenseReport
    {
        return $this->createQueryBuilder('er')
            ->andWhere('er.event = :eventId')
            ->andWhere('er.user = :userId')
            ->setParameter('eventId', $eventId)
            ->setParameter('userId', $userId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
