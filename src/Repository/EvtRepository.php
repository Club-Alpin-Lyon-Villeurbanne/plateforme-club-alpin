<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Evt|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evt|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evt[]    findAll()
 * @method Evt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvtRepository extends ServiceEntityRepository
{
    private int $defaultLimit = 30;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evt::class);
    }

    public function getUnvalidatedEvt(array $commissions = [])
    {
        $sql = 'SELECT count(e.id_evt)
            FROM caf_evt e
            INNER JOIN caf_commission c ON c.id_commission = e.commission_evt
            WHERE status_evt = \'0\'
                AND tsp_evt IS NOT NULL';

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

    /** @return Evt[] */
    public function getUpcomingEvents(?Commission $commission, array $options = [])
    {
        $options = array_merge([
            'limit' => $this->defaultLimit,
        ], $options);
        $date = new \DateTime('today');

        $qb = $this->createQueryBuilder('e')
            ->select('e, c')
            ->leftJoin('e.commission', 'c')
            ->where('e.status = :status')
            ->setParameter('status', Evt::STATUS_LEGAL_VALIDE)
            ->andWhere('e.tsp >= :date')
            ->setParameter('date', $date->getTimestamp())
            ->orderBy('e.tsp', 'asc')
            ->setMaxResults($options['limit'])
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
    public function getUserEvents(User $user, int $first, int $perPage)
    {
        $qb = $this->getUserEventsDql($user)
            ->orderBy('e.tsp', 'desc');

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    public function getUserEventsCount(User $user): int
    {
        return $this
            ->getUserEventsDql($user)
            ->select('count(e)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getUserEventsDql(User $user): QueryBuilder
    {
        return $this->getEventsByUserDql($user);
    }

    /** @return Evt[] */
    public function getUserPastEvents(User $user, int $first, int $perPage)
    {
        $qb = $this->getUserPastEventsDql($user);

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    public function getUserPastEventsCount(User $user): int
    {
        return $this
            ->getUserPastEventsDql($user)
            ->select('count(e)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getUserPastEventsDql(User $user): QueryBuilder
    {
        $date = new \DateTime('today');

        return $this->getEventsByUserDql($user, [Evt::STATUS_LEGAL_VALIDE])
            ->andWhere('e.tspEnd < :date')
            ->setParameter('date', $date->getTimestamp())
            ->orderBy('e.tsp', 'desc');
    }

    /** @return Evt[] */
    public function getUserUpcomingEvents(User $user, int $first, int $perPage)
    {
        $qb = $this->getUserUpcomingEventsDql($user);

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    public function getUserUpcomingEventsCount(User $user): int
    {
        return $this
            ->getUserUpcomingEventsDql($user)
            ->select('count(e)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getUserUpcomingEventsDql(User $user): QueryBuilder
    {
        $date = new \DateTime('today');

        return $this->getEventsByUserDql($user, [Evt::STATUS_LEGAL_VALIDE])
            ->andWhere('e.tspEnd >= :date')
            ->setParameter('date', $date->getTimestamp())
            ->orderBy('e.tsp', 'asc')
        ;
    }

    private function getEventsByUserDql(User $user, array $status = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e')
            // required since there might be multiple participation for a user (weird schema)
            ->distinct(true)
            ->leftJoin('e.commission', 'c')
            ->leftJoin('e.participations', 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
        ;

        if (!empty($status)) {
            $qb = $qb
                ->andWhere('e.status IN (:status)')
                ->setParameter('status', $status)
            ;
        }

        return $qb;
    }

    private function getPaginatedResults(QueryBuilder $qb, int $first, int $perPage)
    {
        return $qb->setFirstResult($first)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }
}
