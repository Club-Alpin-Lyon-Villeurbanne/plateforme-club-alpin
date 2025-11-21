<?php

namespace App\Repository;

use App\Entity\FormationCompetenceReferentiel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationCompetenceReferentiel>
 */
class FormationCompetenceReferentielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationCompetenceReferentiel::class);
    }

    public function getAllCompetencesByCommissionCode(string $code)
    {
        return $this->createQueryBuilder('f')
            ->where('f.codeActivite LIKE :pattern')
            ->setParameter('pattern', $code)
            ->orderBy('f.intitule', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
