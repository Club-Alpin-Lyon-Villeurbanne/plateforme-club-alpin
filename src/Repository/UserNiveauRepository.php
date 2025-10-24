<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNiveau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserNiveau|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserNiveau|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserNiveau[]    findAll()
 * @method UserNiveau[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserNiveauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNiveau::class);
    }

    public function deleteByUser(User $user): void
    {
        $this->createQueryBuilder('un')
            ->delete()
            ->where('un.idUser = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute()
        ;
    }
}
