<?php

namespace App\Repository;

use App\Entity\ExpenseTypeExpenseFieldType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExpenseTypeExpenseFieldType>
 *
 * @method ExpenseTypeExpenseFieldType|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpenseTypeExpenseFieldType|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpenseTypeExpenseFieldType[]    findAll()
 * @method ExpenseTypeExpenseFieldType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpenseTypeExpenseFieldTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpenseTypeExpenseFieldType::class);
    }

    //    /**
    //     * @return ExpenseTypeExpenseFieldType[] Returns an array of ExpenseTypeExpenseFieldType objects
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

    //    public function findOneBySomeField($value): ?ExpenseTypeExpenseFieldType
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
