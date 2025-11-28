<?php

namespace App\Repository;

use App\Entity\FormationValidationNiveauPratique;
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
}
