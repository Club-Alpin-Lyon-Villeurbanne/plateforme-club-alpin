<?php

namespace App\Repository;

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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evt::class);
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
}
