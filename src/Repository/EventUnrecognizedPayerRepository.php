<?php

namespace App\Repository;

use App\Entity\EventUnrecognizedPayer;
use App\Entity\Evt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventUnrecognizedPayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventUnrecognizedPayer::class);
    }

    public function getAllPayerEmailForEvent(Evt $event): array
    {
        $emails = [];
        $unrecognizedPayers = $this->findBy(['event' => $event, 'hasPaid' => true]);

        /** @var EventUnrecognizedPayer $payer */
        foreach ($unrecognizedPayers as $payer) {
            $emails[] = $payer->getEmail();
        }

        return $emails;
    }
}
