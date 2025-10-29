<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Commission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
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
    public function getArticles(?Commission $commission = null, array $options = [])
    {
        $options = array_merge([
            'limit' => 10,
        ], $options);

        $qb = $this->createQueryBuilder('a')
            ->select('a, c')
            ->leftJoin('a.commission', 'c')
            ->where('a.status > 0')
            ->orderBy('a.validationDate', 'DESC')
            ->setMaxResults($options['limit'])
        ;

        if ($commission) {
            $qb = $qb->andWhere('a.commission = :commission_id')
                ->setParameter('commission_id', $commission->getId());
        }

        return $qb
            ->getQuery()
            ->getResult();
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
}
