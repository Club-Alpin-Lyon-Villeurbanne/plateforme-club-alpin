<?php

namespace App\Repository;

use App\Entity\NdfDemande;
use App\Entity\Evt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NdfDemande|null find($id, $lockMode = null, $lockVersion = null)
 * @method NdfDemande|null findOneBy(array $criteria, array $orderBy = null)
 * @method NdfDemande[]    findAll()
 * @method NdfDemande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NdfDemandeRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NdfDemande::class);
    }

    public function getForUserAndEvent(User $user, Evt $event)
    {
        return $this->createQueryBuilder('d')
            ->select('d')
            ->where('d.sortie = :event_id')
            ->andWhere('d.demandeur = :user_id')
            ->setParameter('event_id', $event->getId())
            ->setParameter('user_id', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
