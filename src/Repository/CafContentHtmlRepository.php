<?php

namespace App\Repository;

use App\Entity\CafContentHtml;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CafContentHtml|null find($id, $lockMode = null, $lockVersion = null)
 * @method CafContentHtml|null findOneBy(array $criteria, array $orderBy = null)
 * @method CafContentHtml[]    findAll()
 * @method CafContentHtml[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CafContentHtmlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CafContentHtml::class);
    }

    public function findByCodeContent($codeContent): ?CafContentHtml
    {
        return $this->createQueryBuilder('c')
            ->where('c.codeContentHtml = :code')
            ->setParameter('code', $codeContent)
            ->orderBy('c.dateContentHtml', 'DESC')
            ->getQuery()
            ->getFirstResult()
        ;
    }
}
