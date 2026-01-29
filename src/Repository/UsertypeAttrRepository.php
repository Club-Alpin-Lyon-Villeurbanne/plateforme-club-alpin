<?php

namespace App\Repository;

use App\Entity\UsertypeAttr;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UsertypeAttrRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UsertypeAttr::class);
    }

    /**
     * Retourne toutes les attributions sous forme de tableau de clés 'typeId-rightId'.
     *
     * @return string[]
     */
    public function findAllPairs(): array
    {
        $attributions = $this->createQueryBuilder('a')
            ->select('a.type, a.right')
            ->getQuery()
            ->getArrayResult()
        ;
        $pairs = [];
        foreach ($attributions as $row) {
            $pairs[] = $row['type'] . '-' . $row['right'];
        }

        return $pairs;
    }

    /**
     * Remplace toutes les attributions par celles fournies.
     *
     * @param string[] $pairs
     */
    public function replaceAll(array $pairs): void
    {
        $em = $this->getEntityManager();
        $em->wrapInTransaction(function () use ($em, $pairs) {
            // purge
            $em->createQuery('TRUNCATE TABLE App\\Entity\\UsertypeAttr a')->execute();

            // insertion des valeurs
            foreach ($pairs as $pair) {
                [$typeId, $rightId] = explode('-', $pair);

                $attr = new UsertypeAttr();
                $attr->setType((int) $typeId);
                $attr->setRight((int) $rightId);
                $attr->setDetails(time());
                $em->persist($attr);
            }
            $em->flush();
        });
    }
}
