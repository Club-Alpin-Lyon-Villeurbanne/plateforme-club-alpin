<?php

namespace App\Repository;

use App\Entity\Commission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Commission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Commission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Commission[]    findAll()
 * @method Commission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commission::class);
    }

    /** @return Commission[] */
    public function findVisible(): iterable
    {
        yield from $this->createQueryBuilder('c')
            ->where('c.vis = 1')
            ->orderBy('c.ordre', 'ASC')
            ->getQuery()
            ->toIterable()
        ;
    }

    public function findVisibleCommission(?string $code): ?Commission
    {
        if (null === $code) {
            return null;
        }

        return $this->createQueryBuilder('c')
            ->where('c.vis = 1')
            ->andWhere('c.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllCommissionCodes(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.code')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function getCommissionNameByCode(string $code): ?string
    {
        return $this->findOneBy(['code' => $code])->getTitle();
    }
}
