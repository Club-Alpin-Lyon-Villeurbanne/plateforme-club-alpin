<?php

namespace App\Repository;

use App\Entity\NdfDepenseCommun;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NdfDepenseCommun|null find($id, $lockMode = null, $lockVersion = null)
 * @method NdfDepenseCommun|null findOneBy(array $criteria, array $orderBy = null)
 * @method NdfDepenseCommun[]    findAll()
 * @method NdfDepenseCommun[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NdfDepenseCommunRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NdfDepenseCommun::class);
    }
}
