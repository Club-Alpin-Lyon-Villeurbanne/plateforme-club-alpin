<?php

namespace App\Repository;

use App\Entity\MediaUpload;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MediaUploadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaUpload::class);
    }

    public function findUnusedOlderThan(\DateTimeInterface $date)
    {
        return $this->createQueryBuilder('m')
            ->where('m.used = :used')
            ->andWhere('m.createdAt < :date')
            ->setParameter('used', false)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
}
