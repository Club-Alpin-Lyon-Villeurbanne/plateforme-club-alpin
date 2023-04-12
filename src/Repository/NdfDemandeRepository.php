<?php

namespace App\Repository;

use App\Entity\NdfDemande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NdfDemande|null find($id, $lockMode = null, $lockVersion = null)
 * @method NdfDemande|null findOneBy(array $criteria, array $orderBy = null)
 * @method NdfDemande[]    findAll()
 * @method NdfDemande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NdfDemandeRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NdfDemande::class);
    }
}
