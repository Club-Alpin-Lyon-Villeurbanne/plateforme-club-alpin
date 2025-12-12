<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\FormationValidationGroupeCompetence;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationValidationGroupeCompetence>
 */
class FormationValidationGroupeCompetenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationValidationGroupeCompetence::class);
    }

    public function getAllGroupesCompetencesByUser(User $user, ?Commission $commission = null)
    {
        $qb = $this->createQueryBuilder('g')
            ->innerJoin('g.competence', 'r')
            ->where('g.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.codeActivite', 'asc')
            ->addOrderBy('r.intitule', 'asc')
        ;
        if ($commission) {
            $qb
                ->innerJoin(Commission::class, 'c', Join::WITH, 'r MEMBER OF c.groupesCompetences')
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
