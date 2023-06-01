<?php

namespace App\Repository;

use App\Entity\NdfDepenseAutre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NdfDepenseAutre|null find($id, $lockMode = null, $lockVersion = null)
 * @method NdfDepenseAutre|null findOneBy(array $criteria, array $orderBy = null)
 * @method NdfDepenseAutre[]    findAll()
 * @method NdfDepenseAutre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NdfDepenseAutreRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NdfDepenseAutre::class);
    }
}
