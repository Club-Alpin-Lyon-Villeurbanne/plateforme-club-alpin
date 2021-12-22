<?php

namespace App\Repository;

use App\Entity\ContentInline;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ContentInline|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContentInline|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContentInline[]    findAll()
 * @method ContentInline[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContentInlineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentInline::class);
    }
}
