<?php

namespace App\Repository;

use App\Entity\NdfDepenseHebergement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NdfDepenseHebergement|null find($id, $lockMode = null, $lockVersion = null)
 * @method NdfDepenseHebergement|null findOneBy(array $criteria, array $orderBy = null)
 * @method NdfDepenseHebergement[]    findAll()
 * @method NdfDepenseHebergement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NdfDepenseHebergementRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NdfDepenseHebergement::class);
    }
}
