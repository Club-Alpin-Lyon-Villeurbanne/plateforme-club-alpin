<?php

namespace App\Repository;

use App\Entity\FormationReferentielBrevet;
use App\Entity\FormationValidationBrevet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FormationValidationBrevet|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormationValidationBrevet|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormationValidationBrevet[]    findAll()
 * @method FormationValidationBrevet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationValidationBrevetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationValidationBrevet::class);
    }

    public function deleteByUser(User $user): void
    {
        $this->createQueryBuilder('b')
            ->delete()
            ->where('b.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute()
        ;
    }

    public function getDateByUserAndBrevet(User $user, FormationReferentielBrevet $brevet)
    {
        return $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.brevet = :brevet')
            ->setParameter('user', $user)
            ->setParameter('brevet', $brevet)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
