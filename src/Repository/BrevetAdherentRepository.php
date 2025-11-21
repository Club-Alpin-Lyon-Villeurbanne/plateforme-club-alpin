<?php

namespace App\Repository;

use App\Entity\BrevetAdherent;
use App\Entity\BrevetReferentiel;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BrevetAdherent|null find($id, $lockMode = null, $lockVersion = null)
 * @method BrevetAdherent|null findOneBy(array $criteria, array $orderBy = null)
 * @method BrevetAdherent[]    findAll()
 * @method BrevetAdherent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BrevetAdherentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BrevetAdherent::class);
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

    public function getDateByUserAndBrevet(User $user, BrevetReferentiel $brevet)
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
