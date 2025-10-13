<?php

namespace App\Repository;

use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventParticipation|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventParticipation|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventParticipation[]    findAll()
 * @method EventParticipation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventParticipation::class);
    }

    /** @return EventParticipation[][] */
    public function getEmpietements(Evt $event, ?User $user = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('e, p')
            ->innerJoin('p.evt', 'e')
            ->where('p.status != :status_refuse')
            ->setParameter('status_refuse', EventParticipation::STATUS_REFUSE)
            ->andWhere('p.status != :status_absent')
            ->setParameter('status_absent', EventParticipation::STATUS_ABSENT)
            ->andWhere('e.isDraft = false')
            ->andWhere('e.id != :id')
            ->setParameter('id', $event->getId())
            ->andWhere('e.status != :event_status')
            ->setParameter('event_status', Evt::STATUS_LEGAL_REFUSE)
            ->andWhere('(e.eventStartDate >= :start AND e.eventStartDate <= :end) OR (e.eventEndDate >= :start AND e.eventEndDate <= :end) OR (e.eventStartDate <= :start AND e.eventEndDate >= :end)')
            ->setParameter('start', $event->getEventStartDate())
            ->setParameter('end', $event->getEventEndDate())
            ->orderBy('e.eventStartDate', 'asc')
        ;

        if ($user) {
            $qb = $qb->andWhere('p.user = :user')
                ->setParameter('user', $user);
        }

        /** @var EventParticipation[] $results */
        $results = $qb
            ->getQuery()
            ->getResult();

        $ret = [];

        foreach ($results as $participation) {
            $ret[$participation->getUser()->getId()][] = $participation;
        }

        if ($user) {
            return $ret[$user->getId()] ?? [];
        }

        return $ret;
    }

    /**
     * Retourne la liste des participations triée par rôles.
     *
     * @param Evt        $event
     *                           La sortie
     * @param array|null $roles
     *                           Les rôles à filtrer
     * @param int|null   $status
     *                           Les status à filtrer
     */
    public function getSortedParticipations(Evt $event, ?array $roles = null, ?int $status = EventParticipation::STATUS_VALIDE, bool $sortOnlyByName = false): mixed
    {
        $qb = $this->createQueryBuilder('ej')
            ->select('ej as liste')
            ->addSelect('(SELECT ut.hierarchie
                                    FROM App\Entity\Usertype ut
                                    WHERE ut.code = ej.role
                                ) as weight')
            ->addSelect('CASE WHEN ej.status = 0 THEN 3 ELSE ej.status END as status_sort')
            ->join('ej.user', 'inscrit')
            ->where('ej.evt = :event')
            ->setParameter('event', $event)
        ;
        if (!$sortOnlyByName) {
            $qb
                ->addOrderBy('weight', 'DESC')
                ->addOrderBy('status_sort', 'ASC')
            ;
        }
        $qb
            ->addOrderBy('inscrit.firstname', 'ASC')
            ->addOrderBy('inscrit.lastname', 'ASC')
            ->addOrderBy('ej.createdAt', 'ASC')
        ;

        if ($roles) {
            $qb->andWhere($qb->expr()->in('ej.role', \is_array($roles) ? $roles : (array) $roles));
        }
        if ($status) {
            $qb->andWhere($qb->expr()->in('ej.status', \is_array($status) ? $status : (array) $status));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getEventPresencesAndAbsencesOfUser(int $userId): mixed
    {
        $presences = $absences = 0;
        $query = $this->createQueryBuilder('ep')
            ->select('(ep.user)')
            ->addSelect('COUNT(CASE WHEN ep.status = :present THEN 1 ELSE NULLIF(1,1) END) as presences')
            ->addSelect('COUNT(CASE WHEN ep.status = :absent THEN 1 ELSE NULLIF(1,1) END) as absences')
            ->where('IDENTITY(ep.user) = :id')
            ->setParameters([
                'id' => $userId,
                'present' => EventParticipation::STATUS_VALIDE,
                'absent' => EventParticipation::STATUS_ABSENT,
            ])
            ->groupBy('ep.user')
            ->getQuery()
        ;

        $result = $query->getOneOrNullResult();
        if ($result) {
            list('absences' => $absences, 'presences' => $presences) = $result;
        }

        return ['absences' => $absences, 'presences' => $presences];
    }
}
