<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Commission;
use App\Entity\User;
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

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getUserArticlesCount(User $user, array $statuses = [Article::STATUS_PUBLISHED]): int
    {
        return (int) $this->getUserArticlesDql($user, $statuses)
            ->select('count(a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return Article[] */
    public function getUserArticles(User $user, int $first = 0, int $perPage = 10, array $statuses = [Article::STATUS_PUBLISHED]): array
    {
        $qb = $this->getUserArticlesDql($user, $statuses)
            ->orderBy('a.updatedAt', 'DESC')
        ;

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    protected function getUserArticlesDql(User $user, array $statuses = [Article::STATUS_PUBLISHED]): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
        ;
        if (!empty($statuses)) {
            $qb
                ->andWhere('a.status IN (:status)')
                ->setParameter('status', $statuses)
            ;
        }

        return $qb;
    }

    public function searchArticles(string $searchText, int $limit, ?Commission $commission = null): array
    {
        return $this->getSearchQueryBuilder($searchText, $commission)
            ->setMaxResults($limit)
            ->orderBy('a.validationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getSearchArticlesCount(string $searchText, ?Commission $commission = null): int
    {
        return (int) $this
            ->getSearchQueryBuilder($searchText, $commission)
            ->select('count(a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    protected function getSearchQueryBuilder(string $searchText, ?Commission $commission = null): QueryBuilder
    {
        return $this->getArticlesByCommissionDql($commission)
            ->innerJoin('a.user', 'u')
            ->andWhere('a.titre LIKE :search OR a.cont LIKE :search OR u.nickname LIKE :search')
            ->setParameter('search', '%' . $searchText . '%')
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

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getUnvalidatedArticlesCount(array $commissions = []): int
    {
        $qb = $this->getUnvalidatedArticlesDql($commissions)
            ->select('count(a)')
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return Article[] */
    public function getUnvalidatedArticles(array $commissions = [], int $first = 0, int $perPage = 10): array
    {
        $qb = $this->getUnvalidatedArticlesDql($commissions)
            ->orderBy('a.topubly', 'DESC')
            ->addOrderBy('a.createdAt', 'ASC')
        ;

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    protected function getUnvalidatedArticlesDql(array $commissions = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.topubly = :topubly')
            ->setParameter('status', Article::STATUS_PENDING)
            ->setParameter('topubly', 1)
        ;

        if (!empty($commissions)) {
            $commissionIds = array_map(fn (Commission $c) => $c->getId(), $commissions);

            $qb
                ->leftJoin('a.evt', 'e')
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->in('a.commission', ':commissions'),
                    $qb->expr()->in('e.commission', ':commissions')
                ))
                ->setParameter('commissions', $commissionIds)
            ;
        }

        return $qb;
    }

    /**
     * @return Article[]
     */
    public function getPublishedArticlesForRightColumn(?Commission $commission = null, int $limit = 16): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.une = false')
            ->setParameter('status', Article::STATUS_PUBLISHED)
            ->orderBy('a.updatedAt', 'DESC')
            ->setMaxResults($limit)
        ;

        if ($commission instanceof Commission) {
            $qb
                ->innerJoin('a.commission', 'c')
                ->andWhere('a.commission = :commission')
                ->setParameter('commission', $commission)
            ;
        }

        return $qb->getQuery()->getResult();
    }
}
