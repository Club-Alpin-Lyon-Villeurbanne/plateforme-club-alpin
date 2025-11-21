<?php

namespace App\Repository;

use App\Entity\FormationNiveauValidation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormationNiveauValidation>
 */
class FormationNiveauValidationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationNiveauValidation::class);
    }

    public function getAllNiveauxByUser(User $user)
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.niveauReferentiel', 'r')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.niveauCourt', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
