<?php

namespace App\Repository;

use App\Entity\FormationReferentielBrevet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FormationReferentielBrevet|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormationReferentielBrevet|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormationReferentielBrevet[]    findAll()
 * @method FormationReferentielBrevet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationReferentielBrevetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationReferentielBrevet::class);
    }
}
