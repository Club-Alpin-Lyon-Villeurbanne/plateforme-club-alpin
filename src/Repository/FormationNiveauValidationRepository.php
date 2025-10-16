<?php

namespace App\Repository;

use App\Entity\FormationNiveauValidation;
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
}
