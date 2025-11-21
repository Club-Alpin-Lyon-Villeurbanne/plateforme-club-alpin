<?php

namespace App\Repository;

use App\Entity\BrevetReferentiel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BrevetReferentiel|null find($id, $lockMode = null, $lockVersion = null)
 * @method BrevetReferentiel|null findOneBy(array $criteria, array $orderBy = null)
 * @method BrevetReferentiel[]    findAll()
 * @method BrevetReferentiel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BrevetReferentielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BrevetReferentiel::class);
    }

    public function getAllBrevetsByCommissionCode(string $code)
    {
        $codePattern = 'BF%-' . $code . '%';

        return $this->createQueryBuilder('b')
            ->where('b.codeBrevet LIKE :pattern')
            ->andWhere('b.codeBrevet NOT LIKE :code')
            ->setParameter('pattern', $codePattern)
            ->setParameter('code', 'BFM-%')
            ->orderBy('b.codeBrevet', 'desc')
            ->getQuery()
            ->getResult()
        ;
    }
}
