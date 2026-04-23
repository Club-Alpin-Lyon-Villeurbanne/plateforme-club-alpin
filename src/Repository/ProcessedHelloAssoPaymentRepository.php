<?php

namespace App\Repository;

use App\Entity\ProcessedHelloAssoPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProcessedHelloAssoPayment>
 */
class ProcessedHelloAssoPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProcessedHelloAssoPayment::class);
    }

    public function findOneByHelloAssoPaymentId(string $helloAssoPaymentId): ?ProcessedHelloAssoPayment
    {
        return $this->findOneBy(['helloAssoPaymentId' => $helloAssoPaymentId]);
    }

    public function save(ProcessedHelloAssoPayment $payment): void
    {
        $em = $this->getEntityManager();
        $em->persist($payment);
        $em->flush();
    }
}
