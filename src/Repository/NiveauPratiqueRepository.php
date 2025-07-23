<?php

namespace App\Repository;

use App\Entity\NiveauPratique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NiveauPratique|null find($id, $lockMode = null, $lockVersion = null)
 * @method NiveauPratique|null findOneBy(array $criteria, array $orderBy = null)
 * @method NiveauPratique[]    findAll()
 * @method NiveauPratique[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NiveauPratiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NiveauPratique::class);
    }
}
