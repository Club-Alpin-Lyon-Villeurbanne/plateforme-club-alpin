<?php

namespace App\Repository;

use App\Entity\NdfDepenseMinibusClub;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NdfDepenseMinibusClub|null find($id, $lockMode = null, $lockVersion = null)
 * @method NdfDepenseMinibusClub|null findOneBy(array $criteria, array $orderBy = null)
 * @method NdfDepenseMinibusClub[]    findAll()
 * @method NdfDepenseMinibusClub[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NdfDepenseMinibusClubRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NdfDepenseMinibusClub::class);
    }
}
