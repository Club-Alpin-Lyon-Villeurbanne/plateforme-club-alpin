<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\FormationReferentielNiveauPratique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationReferentielNiveauPratique>
 */
class FormationReferentielNiveauPratiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationReferentielNiveauPratique::class);
    }

    public function getCommissionsByReferentiel(FormationReferentielNiveauPratique $referentiel): array
    {
        return $this->createQueryBuilder('r')
            ->select('c')
            ->innerJoin(Commission::class, 'c', Join::WITH, 'r MEMBER OF c.niveaux')
            ->where('r = :referentiel')
            ->setParameter('referentiel', $referentiel)
            ->orderBy('c.ordre', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
