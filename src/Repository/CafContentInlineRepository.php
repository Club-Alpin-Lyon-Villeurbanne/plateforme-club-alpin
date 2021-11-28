<?php

namespace App\Repository;

use App\Entity\CafContentInline;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CafContentInline|null find($id, $lockMode = null, $lockVersion = null)
 * @method CafContentInline|null findOneBy(array $criteria, array $orderBy = null)
 * @method CafContentInline[]    findAll()
 * @method CafContentInline[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CafContentInlineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CafContentInline::class);
    }
}
