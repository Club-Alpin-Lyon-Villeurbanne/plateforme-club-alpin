<?php

namespace App\Repository;

use App\Entity\FormationReferentielNiveauPratique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
