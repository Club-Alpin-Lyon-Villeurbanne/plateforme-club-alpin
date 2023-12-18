<?php

namespace App\Repository;

use App\Entity\ExpenseGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExpenseGroup>
 *
 * @method ExpenseGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpenseGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpenseGroup[]    findAll()
 * @method ExpenseGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpenseGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpenseGroup::class);
    }

//    /**
//     * @return ExpenseGroup[] Returns an array of ExpenseGroup objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ExpenseGroup
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
