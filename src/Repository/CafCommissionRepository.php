<?php

namespace App\Repository;

use App\Entity\CafCommission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CafCommission|null find($id, $lockMode = null, $lockVersion = null)
 * @method CafCommission|null findOneBy(array $criteria, array $orderBy = null)
 * @method CafCommission[]    findAll()
 * @method CafCommission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CafCommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CafCommission::class);
    }

    /** @return CafCommission[] */
    public function findVisible(): iterable
    {
        yield from $this->createQueryBuilder('c')
            ->where('c.visCommission = 1')
            ->orderBy('c.ordreCommission', 'ASC')
            ->getQuery()
            ->toIterable()
        ;
    }

    public function findVisibleCommission(string $code): ?CafCommission
    {
        return $this->createQueryBuilder('c')
            ->where('c.visCommission = 1')
            ->andWhere('c.codeCommission = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
