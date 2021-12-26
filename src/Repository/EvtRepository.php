<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\Evt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Evt|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evt|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evt[]    findAll()
 * @method Evt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvtRepository extends ServiceEntityRepository
{
    private int $maxSortiesAccueil;

    public function __construct(ManagerRegistry $registry, int $maxSortiesAccueil)
    {
        parent::__construct($registry, Evt::class);
        $this->maxSortiesAccueil = $maxSortiesAccueil;
    }

    public function getUnvalidatedEvt(array $commissions = [])
    {
        $sql = 'SELECT count(e.id_evt)
            FROM caf_evt e
            INNER JOIN caf_commission c ON c.id_commission = e.commission_evt
            WHERE status_evt = \'0\'';

        $params = [];
        $sqlPart = [];

        foreach ($commissions as $key => $commission) {
            $params['com_'.$key] = $commission;
            $sqlPart[] = ' c.code_commission = :com_'.$key;
        }

        if (!empty($sqlPart)) {
            $sql .= ' AND ('.implode(' OR ', $sqlPart).')';
        }

        return $this->_em->getConnection()->fetchOne($sql, $params);
    }

    public function getUnvalidatedPresidentEvt()
    {
        $sql = 'SELECT count(id_evt) FROM caf_evt
            WHERE status_legal_evt = 0
                AND status_evt = 1
                AND tsp_evt > :datemin
                AND tsp_evt < :datemax';

        return $this->_em->getConnection()->fetchOne($sql, [
            'datemin' => time(),
            'datemax' => strtotime('midnight +8 days'),
        ]);
    }

    public function getUpcomingEvents(?Commission $commission)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e, c')
            ->leftJoin('e.commission', 'c')
            ->where('e.status > 0')
            ->andWhere('e.tsp > :date')
            ->setParameter('date', time())
            ->orderBy('e.tsp', 'asc')
            ->setMaxResults($this->maxSortiesAccueil)
        ;

        if ($commission) {
            $qb = $qb->andWhere('c.id = :commission_id')
                ->setParameter('commission_id', $commission->getId());
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    /** @return Evt[] */
    public function getEvents(int $quantity, ?Commission $commission = null)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e, c')
            ->leftJoin('e.commission', 'c')
            ->where('e.status > 0')
            ->andWhere('e.tsp > :now')
            ->setParameter('now', time())
            ->orderBy('e.tsp', 'ASC')
            ->setMaxResults($quantity)
        ;

        if ($commission) {
            $qb = $qb->andWhere('e.commission = :commission_id')
                ->setParameter('commission_id', $commission->getId());
        }

        return $qb
            ->getQuery()
            ->getResult();
    }
}
