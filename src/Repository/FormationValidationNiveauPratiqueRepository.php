<?php

namespace App\Repository;

use App\Entity\FormationValidationNiveauPratique;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
