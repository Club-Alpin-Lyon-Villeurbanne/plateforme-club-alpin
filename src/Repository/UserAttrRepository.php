<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Entity\Usertype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserAttr|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAttr|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserAttr[]    findAll()
 * @method UserAttr[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserAttrRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAttr::class);
    }

    /* @return UserAttr[] */
    public function listAllEncadrants(Commission $commission, $types = [
        UserAttr::RESPONSABLE_COMMISSION,
        UserAttr::ENCADRANT,
        UserAttr::STAGIAIRE,
        UserAttr::COENCADRANT,
        UserAttr::BENEVOLE,
    ])
    {
        $dql = 'SELECT a
                FROM ' . User::class . ' u, ' . Usertype::class . ' t, ' . UserAttr::class . ' a
                WHERE
                    a.user = u.id
                    AND t.code IN (:types)
                    AND a.userType = t.id
                    AND u.doitRenouveler = 0
                    AND a.params LIKE \'commission:' . $commission->getCode() . '\'
                ORDER BY t.hierarchie, u.firstname ASC
        ';

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('types', $types);

        // some users may appear multiple time because they have multiple attributes (resp. de commission + encadrant)
        // the list is ordered by hierarchy
        // let's keep the first occurence only
        $seen[] = [];
        foreach ($query->getResult() as $res) {
            $id = $res->getUser()->getId();

            if (isset($seen[$id])) {
                continue;
            }

            yield $res;

            $seen[$id] = true;
        }
    }
}
