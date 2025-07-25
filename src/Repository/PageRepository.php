<?php

namespace App\Repository;

use App\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
