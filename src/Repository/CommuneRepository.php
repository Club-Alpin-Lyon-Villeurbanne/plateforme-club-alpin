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

    /**
     * Résout strictement un libellé saisi vers une commune du référentiel.
     * Restreint la requête au code postal (5 chiffres de tête, jamais reformaté),
     * puis compare le libellé canonique entier. Retourne null si aucune correspondance.
     */
    public function findOneByLabel(string $place): ?Commune
    {
        if (!preg_match('/^\s*(\d{5})/', $place, $matches)) {
            return null;
        }

        $needle = mb_strtolower(trim($place));
        // tri par id pour une résolution déterministe en cas d'homonymes exacts
        foreach ($this->findBy(['codePostal' => $matches[1]], ['id' => 'ASC']) as $commune) {
            if (mb_strtolower(trim($commune->getLabel())) === $needle) {
                return $commune;
            }
        }

        return null;
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
