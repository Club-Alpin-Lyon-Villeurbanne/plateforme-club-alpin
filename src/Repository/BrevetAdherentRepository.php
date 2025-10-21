<?php

namespace App\Repository;

use App\Entity\BrevetAdherent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BrevetAdherent|null find($id, $lockMode = null, $lockVersion = null)
 * @method BrevetAdherent|null findOneBy(array $criteria, array $orderBy = null)
 * @method BrevetAdherent[]    findAll()
 * @method BrevetAdherent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BrevetAdherentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BrevetAdherent::class);
    }
}
