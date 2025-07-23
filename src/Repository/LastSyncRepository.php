<?php

namespace App\Repository;

use App\Entity\LastSync;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LastSync|null find($id, $lockMode = null, $lockVersion = null)
 * @method LastSync|null findOneBy(array $criteria, array $orderBy = null)
 * @method LastSync[]    findAll()
 * @method LastSync[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LastSyncRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LastSync::class);
    }
}
