<?php

namespace App\Repository;

use App\Entity\FormationNiveauReferentiel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationNiveauReferentiel>
 */
class FormationNiveauReferentielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationNiveauReferentiel::class);
    }

    public function getAllNiveauxByCommissionCode(string $code)
    {
        return $this->createQueryBuilder('b')
            ->where('b.codeActivite = :code')
            ->setParameter('code', $code)
            ->orderBy('b.niveauCourt', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
