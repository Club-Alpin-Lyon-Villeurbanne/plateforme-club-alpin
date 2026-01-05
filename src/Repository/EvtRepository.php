<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Trait\PaginationRepositoryTrait;
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
    use PaginationRepositoryTrait;

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
                AND start_date IS NOT NULL
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
                AND start_date > :datemin
                AND start_date < :datemax';

        $now = new \DateTime();
        $max = (clone $now)->modify('+8 days');

        return $this->_em->getConnection()->fetchOne($sql, [
            'datemin' => $now,
            'datemax' => $max,
        ]);
    }

    /** @return Evt[] */
    public function getUpcomingEvents(?Commission $commission, array $options = [])
    {
        $options = array_merge([
            'limit' => $this->defaultLimit,
        ], $options);
        $date = new \DateTime('today');

        $limitDateStart = null;
        $limitEndDate = null;
        if (isset($options['start_in_days']) && \is_int($options['start_in_days'])) {
            $limitDateStart = new \DateTime();
            $limitDateStart->modify('+' . $options['start_in_days'] . ' days');
            $limitDateStart->setTime(0, 0, 0);

            $limitEndDate = new \DateTime();
            $limitEndDate->modify('+' . $options['start_in_days'] . ' days');
            $limitEndDate->setTime(23, 59, 59);
        }

        $qb = $this->createQueryBuilder('e')
            ->select('e, c')
            ->leftJoin('e.commission', 'c')
            ->where('e.status = :status')
            ->setParameter('status', Evt::STATUS_PUBLISHED_VALIDE)
            ->andWhere(':date <= e.startDate OR :date <= e.endDate')
            ->setParameter('date', $date)
        ;
        if ($limitDateStart) {
            $qb
                ->andWhere('e.startDate >= :limitDate AND e.startDate <= :limitDateEnd')
                ->setParameter('limitDate', $limitDateStart)
                ->setParameter('limitDateEnd', $limitEndDate)
            ;
        }
        $qb
            ->orderBy('e.startDate', 'asc')
            ->addOrderBy('c.title', 'ASC')
            ->addOrderBy('e.titre', 'ASC')
            ->setMaxResults($options['limit'])
        ;

        if ($commission) {
            $qb
                ->andWhere('c.id = :commission_id')
                ->setParameter('commission_id', $commission->getId())
            ;
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return Evt[] */
    public function getUserEvents(User $user, int $first, int $perPage, array $status = [])
    {
        $qb = $this->getUserEventsDql($user, $status)
            ->orderBy('e.startDate', 'desc');

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    /** @return Evt[] */
    public function getUserCreatedEvents(User $user, int $first, int $perPage): array
    {
        $qb = $this->getEventsCreatedByUserDql($user)
            ->addSelect('CASE WHEN e.startDate IS NULL THEN 1 ELSE 0 END as HIDDEN date_is_null')
            ->orderBy('date_is_null', 'desc')
            ->addOrderBy('e.startDate', 'desc')
        ;

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getUserCreatedEventsCount(User $user): int
    {
        return (int) $this
            ->getEventsCreatedByUserDql($user)
            ->select('count(e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getUserEventsCount(User $user, array $status = []): int
    {
        return (int) $this
            ->getUserEventsDql($user, $status)
            ->select('count(e)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getUserEventsDql(User $user, array $status = []): QueryBuilder
    {
        return $this->getEventsByUserDql($user, $status);
    }

    /** @return Evt[] */
    public function getUserPastEvents(User $user, int $first, int $perPage)
    {
        $qb = $this->getUserPastEventsDql($user);

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getUserPastEventsCount(User $user): int
    {
        return (int) $this
            ->getUserPastEventsDql($user)
            ->select('count(e)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getUserPastEventsDql(User $user): QueryBuilder
    {
        $date = new \DateTime();

        return $this->getEventsByUserDql($user, [Evt::STATUS_PUBLISHED_VALIDE])
            ->addSelect('er.status as exp_status')
            ->leftJoin(ExpenseReport::class, 'er', 'WITH', 'er.event = e.id AND er.user = :user')
            ->andWhere('e.startDate IS NOT NULL')
            ->andWhere('e.endDate < :date')
            ->setParameter('date', $date)
            ->setParameter('user', $user)
            ->orderBy('e.startDate', 'desc')
        ;
    }

    /** @return Evt[] */
    public function getUserUpcomingEvents(User $user, int $first, int $perPage)
    {
        $qb = $this->getUserUpcomingEventsDql($user);

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getUserUpcomingEventsCount(User $user): int
    {
        return (int) $this
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
            ->andWhere('e.endDate < :date')
            ->andWhere('e.startDate > :limitDate')
            ->setParameter('status', Evt::STATUS_PUBLISHED_VALIDE)
            ->setParameter('date', new \DateTime())
            ->setParameter('limitDate', $limitDate)
            ->orderBy('e.startDate', 'desc')
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

    public function getEventsToPublish(array $commissions, int $first, int $perPage)
    {
        $qb = $this->getEventsToPublishQueryBuilder($commissions)
            ->orderBy('e.startDate', 'ASC')
        ;

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    public function getAllEventsToPublish(array $commissions = [])
    {
        return $this->getEventsToPublishQueryBuilder($commissions)
            ->orderBy('e.commission', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getEventsToPublishCount(array $commissions): int
    {
        return (int) $this
            ->getEventsToPublishQueryBuilder($commissions)
            ->select('count(e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function getEventsToLegalValidate(int $dateMax, int $first, int $perPage)
    {
        $qb = $this->getEventsToLegalValidateQueryBuilder($dateMax)
            ->orderBy('e.startDate', 'ASC')
        ;

        return $this->getPaginatedResults($qb, $first, $perPage);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getEventsToLegalValidateCount(int $dateMax): int
    {
        return (int) $this
            ->getEventsToLegalValidateQueryBuilder($dateMax)
            ->select('count(e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    protected function getEventsToPublishQueryBuilder(array $commissions): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.isDraft = false')
            ->andWhere('e.startDate IS NOT NULL')
            ->setParameter('status', Evt::STATUS_PUBLISHED_UNSEEN)
        ;
        if (!empty($commissions)) {
            $qb
                ->andWhere('e.commission IN (:commissions)')
                ->setParameter('commissions', $commissions)
            ;
        }

        return $qb;
    }

    protected function getEventsToLegalValidateQueryBuilder(int $dateMax): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.statusLegal = :legal')
            ->andWhere('e.startDate > :dateNow')
            ->andWhere('e.startDate < :dateMax')
            ->setParameter('status', Evt::STATUS_PUBLISHED_VALIDE)
            ->setParameter('legal', Evt::STATUS_LEGAL_UNSEEN)
            ->setParameter('dateNow', new \DateTimeImmutable())
            ->setParameter('dateMax', (new \DateTimeImmutable())->setTimestamp($dateMax))
        ;
    }

    private function getUserUpcomingEventsDql(User $user): QueryBuilder
    {
        $date = new \DateTime('today');

        return $this->getEventsByUserDql($user, [Evt::STATUS_PUBLISHED_VALIDE])
            ->andWhere('e.endDate >= :date')
            ->setParameter('date', $date)
            ->orderBy('e.startDate', 'asc')
        ;
    }

    private function getEventsByUserDql(User $user, array $status = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e, p')
            // required since there might be multiple participation for a user (weird schema)
            ->distinct(true)
            ->innerJoin('e.commission', 'c')
            ->innerJoin('e.participations', 'p')
            ->where('p.user = :user')
            ->andWhere('e.startDate IS NOT NULL')
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
}
