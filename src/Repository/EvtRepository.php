<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\ExpenseReport;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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
                AND tsp_evt IS NOT NULL
                AND is_draft = 0';

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

    public function getEventsToLegalValidate(int $dateMax, int $first, int $perPage)
    {
        $qb = $this->getEventsToLegalValidateQueryBuilder($dateMax)
            ->orderBy('e.tsp', 'ASC')
        ;

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getEventsToLegalValidateCount(int $dateMax): float|bool|int|string|null
    {
        return $this
            ->getEventsToLegalValidateQueryBuilder($dateMax)
            ->select('count(e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    protected function getEventsToLegalValidateQueryBuilder(int $dateMax): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.statusLegal = :legal')
            ->andWhere('e.tsp > :dateNow')
            ->andWhere('e.tsp < :dateMax')
            ->setParameter('status', Evt::STATUS_PUBLISHED_VALIDE)
            ->setParameter('legal', Evt::STATUS_LEGAL_UNSEEN)
            ->setParameter('dateNow', time())
            ->setParameter('dateMax', $dateMax)
        ;
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
            ->andWhere(':date <= e.tsp OR :date <= e.tspEnd')
            ->setParameter('date', $date->getTimestamp())
            ->orderBy('e.tsp', 'asc')
            ->addOrderBy('c.title', 'ASC')
            ->addOrderBy('e.titre', 'ASC')
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

    /** @return Evt[] */
    public function getUserCreatedEvents(User $user, int $first, int $perPage): array
    {
        $qb = $this->getEventsCreatedByUserDql($user)
            ->orderBy('e.tsp', 'desc')
        ;

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getUserCreatedEventsCount(User $user): float|bool|int|string|null
    {
        return $this
            ->getEventsCreatedByUserDql($user)
            ->select('count(e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
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
        $date = new \DateTime();

        return $this->getEventsByUserDql($user, [Evt::STATUS_LEGAL_VALIDE])
            ->addSelect('er.status as exp_status')
            ->leftJoin(ExpenseReport::class, 'er', 'WITH', 'er.event = e.id AND er.user = :user')
            ->andWhere('e.tspEnd < :date')
            ->setParameter('date', $date->getTimestamp())
            ->setParameter('user', $user)
            ->orderBy('e.tsp', 'desc')
        ;
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

    public function getRecentPastEvents(?Commission $commission = null): array
    {
        $limitDate = new \DateTime('last year');

        $queryBuilder = $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.tspEnd < :date')
            ->andWhere('e.tsp > :limitDate')
            ->setParameter('status', Evt::STATUS_PUBLISHED_VALIDE)
            ->setParameter('date', time())
            ->setParameter('limitDate', $limitDate->getTimestamp())
            ->orderBy('e.tsp', 'desc')
        ;
        if ($commission) {
            $queryBuilder
                ->andWhere('e.commission = :commission')
                ->setParameter('commission', $commission)
            ;
        }

        return $queryBuilder
            ->getQuery()
            ->getResult()
        ;
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
            ->select('e, p')
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

    private function getEventsCreatedByUserDql(User $user, array $status = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e')
            ->distinct(true)
            ->where('e.user = :user')
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
