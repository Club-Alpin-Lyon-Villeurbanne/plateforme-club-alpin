<?php

namespace App\Repository;

use App\Entity\Destination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Destination|null find($id, $lockMode = null, $lockVersion = null)
 * @method Destination|null findOneBy(array $criteria, array $orderBy = null)
 * @method Destination[]    findAll()
 * @method Destination[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DestinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Destination::class);
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
