<?php

namespace App\Repository;

use App\Entity\CafDestination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CafDestination|null find($id, $lockMode = null, $lockVersion = null)
 * @method CafDestination|null findOneBy(array $criteria, array $orderBy = null)
 * @method CafDestination[]    findAll()
 * @method CafDestination[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CafDestinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CafDestination::class);
    }

    public function getCountFutureUnpublishedDestinations(): int
    {
        return $this->createQueryBuilder('d')
            ->select('count(d.id)')
            ->where('d.date > :date')
            ->andWhere('d.publie = 0')
            ->setParameter('date', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
