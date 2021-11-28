<?php

namespace App\Repository;

use App\Entity\CafArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CafArticle|null find($id, $lockMode = null, $lockVersion = null)
 * @method CafArticle|null findOneBy(array $criteria, array $orderBy = null)
 * @method CafArticle[]    findAll()
 * @method CafArticle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CafArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CafArticle::class);
    }

    public function getUnvalidatedArticle(array $commissions = [])
    {
        $sql = 'SELECT COUNT(a.id_article)
            FROM caf_article a
            INNER JOIN caf_commission c ON c.id_commission = a.commission_article
            WHERE a.status_article=0
            AND a.topubly_article=1';

        $params = [];
        $sqlPart = [];

        foreach ($commissions as $key => $commission) {
            $params['com_'.$key] = $commission;
            $sqlPart[] = ' c.code_commission = :com_'.$key;
        }

        if (!empty($sqlPart)) {
            $sql .= ' AND ('.implode(') OR (', $sqlPart).')';
        }

        return $this->_em->getConnection()->fetchOne($sql, $params);
    }
}
