<?php

namespace App\Repository;

use App\Entity\FormationValidee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FormationValidee|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormationValidee|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormationValidee[]    findAll()
 * @method FormationValidee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationValideeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationValidee::class);
    }
}
