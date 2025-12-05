<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\FormationValidationNiveauPratique;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationValidationNiveauPratique>
 */
class FormationValidationNiveauPratiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationValidationNiveauPratique::class);
    }

    public function getAllNiveauxByUser(User $user, ?Commission $commission = null)
    {
        $qb = $this->createQueryBuilder('l')
            ->innerJoin('l.niveauReferentiel', 'r')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.niveauCourt', 'asc')
        ;
        if ($commission) {
            $qb
                ->innerJoin(Commission::class, 'c', Join::WITH, 'r MEMBER OF c.niveaux')
                ->andWhere('c = :commission')
                ->setParameter('commission', $commission)
            ;
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }
}
