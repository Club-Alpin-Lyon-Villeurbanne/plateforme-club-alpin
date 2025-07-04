<?php

namespace App\Repository;

use App\Entity\Commune;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Commune|null find($id, $lockMode = null, $lockVersion = null)
 * @method Commune|null findOneBy(array $criteria, array $orderBy = null)
 * @method Commune[]    findAll()
 * @method Commune[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommuneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commune::class);
    }

    public function search(string $requestText = ''): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.codePostal like :search')
            ->orWhere('c.nomCommune like :search')
            ->orWhere('c.libelleAcheminement like :search')
            ->orWhere('c.ligne5 like :search')
            ->setParameter('search', $requestText . '%')
            ->orderBy('c.codePostal', 'ASC')
            ->addOrderBy('c.nomCommune', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }
}
