<?php

namespace App\Repository;

use App\Entity\Evt;
use App\Entity\EvtJoin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EvtJoin|null find($id, $lockMode = null, $lockVersion = null)
 * @method EvtJoin|null findOneBy(array $criteria, array $orderBy = null)
 * @method EvtJoin[]    findAll()
 * @method EvtJoin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvtJoinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvtJoin::class);
    }

    /** @return EvtJoin[][] */
    public function getEmpietements(Evt $event)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('e, p')
            ->innerJoin('p.evt', 'e')
            ->where('p.status != :status_refuse')
            ->setParameter('status_refuse', EvtJoin::STATUS_REFUSE)
            ->andWhere('p.status != :status_absent')
            ->setParameter('status_absent', EvtJoin::STATUS_ABSENT)
            ->andWhere('e.id != :id')
            ->setParameter('id', $event->getId())
            ->andWhere('e.status != :event_status')
            ->setParameter('event_status', Evt::STATUS_LEGAL_REFUSE)
            ->andWhere('(e.tsp >= :start AND e.tsp <= :end) OR (e.tspEnd >= :start AND e.tspEnd <= :end) OR (e.tsp <= :start AND e.tspEnd >= :end)')
            ->setParameter('start', $event->getTsp())
            ->setParameter('end', $event->getTspEnd())
            ->orderBy('e.tsp', 'asc')
        ;

        /** @var EvtJoin[] $results */
        $results = $qb
            ->getQuery()
            ->getResult();

        $ret = [];

        foreach ($results as $participant) {
            $ret[$participant->getUser()->getId()][] = $participant;
        }

        return $ret;
    }

    /**
     * Retourne la liste des participants triée par rôles.
     *
     * @param Evt $event
     *   La sortie.
     * @param $roles
     *   Les rôles à filtrer.
     * @param $status
     *   Les status à filtrer.
     * @return mixed
     */
    public function getSortedParticipants(Evt $event, $roles = null, $status = EvtJoin::STATUS_VALIDE): mixed
    {
        $qb = $this->createQueryBuilder('ej')
            ->select('ej as liste')
            ->addSelect('(SELECT ut.hierarchie
                                    FROM App\Entity\Usertype ut
                                    WHERE ut.code = ej.role
                                ) as weight')
            ->where('ej.evt = :event')
            ->setParameter('event', $event)
            ->orderBy('weight', 'DESC')
            ->addOrderBy('ej.tsp', 'ASC')
        ;

        if ($roles) {
            $qb->andWhere($qb->expr()->in('ej.role', \is_array($roles) ? $roles : (array) $roles));
        }
        if ($status) {
            $qb->andWhere($qb->expr()->in('ej.status', \is_array($status) ? $status : (array) $status));
        }

        return $qb->getQuery()->getResult();
    }
}
