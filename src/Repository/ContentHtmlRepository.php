<?php

namespace App\Repository;

use App\Entity\ContentHtml;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ContentHtml|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContentHtml|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContentHtml[]    findAll()
 * @method ContentHtml[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContentHtmlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentHtml::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByCodeContent($codeContent): ?ContentHtml
    {
        return $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.code = :code')
            ->setParameter('code', $codeContent)
            ->orderBy('c.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}