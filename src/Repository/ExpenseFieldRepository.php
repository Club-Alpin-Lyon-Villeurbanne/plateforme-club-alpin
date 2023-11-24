<?php

namespace App\Repository;

use App\Entity\ExpenseField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExpenseField>
 *
 * @method ExpenseField|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpenseField|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpenseField[]    findAll()
 * @method ExpenseField[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpenseFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpenseField::class);
    }

//    /**
//     * @return ExpenseField[] Returns an array of ExpenseField objects
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

//    public function findOneBySomeField($value): ?ExpenseField
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
