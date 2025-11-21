<?php

namespace App\Repository;

use App\Entity\FormationReferentiel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationReferentiel>
 */
class FormationReferentielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationReferentiel::class);
    }

    public function getAllFormationsByCommissionCode(string $code)
    {
        $codePattern = 'STG-F' . $code . '%';

        return $this->createQueryBuilder('f')
            ->where('f.codeFormation LIKE :pattern')
            ->setParameter('pattern', $codePattern)
            ->orderBy('f.codeFormation', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
