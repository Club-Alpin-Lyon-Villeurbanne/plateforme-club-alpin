<?php

namespace App\Repository;

use App\Entity\ContentHtml;
use App\Entity\ContentInline;
use App\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Page|null find($id, $lockMode = null, $lockVersion = null)
 * @method Page|null findOneBy(array $criteria, array $orderBy = null)
 * @method Page[]    findAll()
 * @method Page[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function getAdminPages(bool $isContentManager = false, bool $isSuperAdmin = false): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.vis = true OR p.vis = :enabled')
            ->setParameter('enabled', !$isContentManager)
            ->orderBy('p.ordreMenu', 'ASC')
            ->addOrderBy('p.ordreMenuadmin', 'ASC')
        ;
        if (!$isContentManager) {
            $queryBuilder
                ->andWhere('p.admin = :visible')
                ->setParameter('visible', false)
            ;
        }
        if (!$isSuperAdmin) {
            $queryBuilder
                ->andWhere('p.superadmin = :visibleAdmin')
                ->setParameter('visibleAdmin', false)
            ;
        }

        return $queryBuilder
            ->getQuery()
            ->getResult()
        ;
    }

    public function searchPages(string $searchText, int $limit): array
    {
        return $this->getSearchQueryBuilder($searchText)
            ->setMaxResults($limit)
            ->orderBy('p.created', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getSearchPagesCount(string $searchText): int
    {
        return (int) $this
            ->getSearchQueryBuilder($searchText)
            ->select('count(DISTINCT p)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    protected function getSearchQueryBuilder(string $searchText): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->innerJoin(ContentHtml::class, 'c', Join::WITH, "c.code = CONCAT('main-pagelibre-', p.id) AND c.current = 1")
            ->innerJoin(ContentInline::class, 'i', Join::WITH, "i.code = CONCAT('meta-title-', p.code)")
            ->where('p.pagelibre = true')
            ->andWhere('p.vis = true')
            ->andWhere('p.defaultName LIKE :search OR c.contenu LIKE :search OR i.contenu LIKE :search')
            ->setParameter('search', '%' . $searchText . '%')
        ;
    }
}
