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
            ->where('p.status != :status_absent')
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
}
