<?php

namespace App\Repository;

use App\Entity\Partenaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method Partenaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method Partenaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method Partenaire[]    findAll()
 * @method Partenaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartenaireRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partenaire::class);
    }

    public function findEnabled(): iterable
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.enable = 1')
            ->andWhere('p.name IS NOT NULL')
            ->andWhere('p.name != \'\'')
            ->andWhere('p.image IS NOT NULL')
            ->andWhere('p.image != \'\'')
            ->orderBy('p.order')
            ->getQuery()
            ->getResult();
    }
}
