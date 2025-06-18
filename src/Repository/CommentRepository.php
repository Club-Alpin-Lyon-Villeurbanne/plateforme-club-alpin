<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findByArticle(Article $article): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parentType = :parentType')
            ->andWhere('c.parent = :article')
            ->andWhere('c.status = :status')
            ->setParameter('parentType', Comment::ARTICLE_TYPE)
            ->setParameter('article', $article)
            ->setParameter('status', 1)
            ->getQuery()
            ->getResult()
        ;
    }
}
