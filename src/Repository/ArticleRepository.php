<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Commission;
use App\Trait\PaginationRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    use PaginationRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function getUnvalidatedArticle(array $commissions = [])
    {
        $sql = 'SELECT COUNT(a.id_article)
            FROM caf_article a';

        if (!empty($commissions)) {
            $sql .= ' LEFT JOIN caf_evt e ON (e.id_evt = a.evt_article)
            INNER JOIN caf_commission c ON (c.id_commission = a.commission_article OR c.id_commission = e.commission_evt) ';
        }

        $sql .= ' WHERE a.status_article=0 AND a.topubly_article=1';

        $params = [];
        $sqlPart = [];

        foreach ($commissions as $key => $commission) {
            $params['com_' . $key] = $commission;
            $sqlPart[] = ' c.code_commission = :com_' . $key;
        }

        if (!empty($sqlPart)) {
            $sql .= ' AND (' . implode(' OR ', $sqlPart) . ')';
        }

        return $this->_em->getConnection()->fetchOne($sql, $params);
    }

    /** @return Article[] */
    public function getArticles(int $first = 0, int $perPage = 10, ?Commission $commission = null): array
    {
        $qb = $this->getArticlesByCommissionDql($commission)
            ->orderBy('a.validationDate', 'DESC')
        ;

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    public function updateViews(Article $article): int
    {
        return $this->createQueryBuilder('a')
            ->update()
            ->set('a.nbVues', 'a.nbVues + 1')
            ->where('a.id = :id_article')
            ->setParameter('id_article', $article->getId())
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getArticlesByCommissionCount(?Commission $commission = null): int
    {
        return (int) $this
            ->getArticlesByCommissionDql($commission)
            ->select('count(a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    protected function getArticlesByCommissionDql(?Commission $commission = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a')
            ->where('a.status = :status')
            ->setParameter('status', Article::STATUS_PUBLISHED)
        ;

        if ($commission instanceof Commission) {
            $qb = $qb
                ->andWhere('a.commission = :commission')
                ->setParameter('commission', $commission)
            ;
        }

        return $qb;
    }
}
