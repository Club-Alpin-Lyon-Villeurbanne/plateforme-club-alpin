<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\FormationReferentielGroupeCompetence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationReferentielGroupeCompetence>
 */
class FormationReferentielGroupeCompetenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationReferentielGroupeCompetence::class);
    }

    public function getCommissionsByReferentiel(FormationReferentielGroupeCompetence $referentiel): array
    {
        return $this->createQueryBuilder('r')
            ->select('c')
            ->innerJoin(Commission::class, 'c', Join::WITH, 'r MEMBER OF c.groupesCompetences')
            ->where('r = :referentiel')
            ->setParameter('referentiel', $referentiel)
            ->orderBy('c.ordre', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
