<?php

namespace App\Repository;

use App\Entity\FormationCompetenceValidation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationCompetenceValidation>
 */
class FormationCompetenceValidationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationCompetenceValidation::class);
    }

    public function getAllGroupesCompetencesByUser(User $user)
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.competence', 'r')
            ->where('g.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.intitule', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
