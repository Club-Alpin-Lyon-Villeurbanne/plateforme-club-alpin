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

    public function listAllEncadrants(Commission $commission, $types = [
        UserAttr::RESPONSABLE_COMMISSION,
        UserAttr::ENCADRANT,
        UserAttr::STAGIAIRE,
        UserAttr::COENCADRANT,
        UserAttr::BENEVOLE,
    ]): \Generator
    {
        $dql = 'SELECT a
                FROM ' . User::class . ' u, ' . Usertype::class . ' t, ' . UserAttr::class . ' a
                WHERE
                    a.user = u.id
                    AND t.code IN (:types)
                    AND a.userType = t.id
                    AND u.doitRenouveler = 0
                    AND u.isDeleted = 0
                    AND a.params LIKE \'commission:' . $commission->getCode() . '\'
                ORDER BY t.hierarchie DESC, u.firstname ASC, u.lastname ASC
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

    public function listAllResponsables(): \Generator
    {
        $dql = 'SELECT a
                FROM ' . User::class . ' u, ' . Usertype::class . ' t, ' . UserAttr::class . ' a
                WHERE
                    a.user = u.id
                    AND t.code = :type
                    AND a.userType = t.id
                    AND u.doitRenouveler = 0
                ORDER BY t.hierarchie DESC, u.firstname ASC, u.lastname ASC
        ';

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('type', UserAttr::RESPONSABLE_COMMISSION);

        // some users may appear multiple time because they are responsables multiple times
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

    public function getResponsablesByCommission(Commission $commission)
    {
        return $this->createQueryBuilder('ua')
            ->innerJoin('ua.user', 'u')
            ->innerJoin('ua.userType', 'ut')
            ->where('ua.params = :commission')
            ->andWhere('ut.code = :type')
            ->setParameter('commission', 'commission:' . $commission->getCode())
            ->setParameter('type', UserAttr::RESPONSABLE_COMMISSION)
            ->getQuery()
            ->getResult()
        ;
    }

    public function listAllManagement(array $types = [UserAttr::VICE_PRESIDENT, UserAttr::PRESIDENT]): \Generator
    {
        $dql = 'SELECT a
                FROM ' . User::class . ' u, ' . Usertype::class . ' t, ' . UserAttr::class . ' a
                WHERE
                    a.user = u.id
                    AND t.code IN (:types)
                    AND a.userType = t.id
                    AND u.doitRenouveler = 0
                ORDER BY t.hierarchie DESC, u.firstname ASC, u.lastname ASC
        ';

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('types', $types);

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

    public function listAllUsersByRole(Usertype $usertype)
    {
        $queryBuilder = $this->createQueryBuilder('ua')
            ->innerJoin('ua.user', 'u')
             ->where('ua.userType = :type')
            ->andWhere('u.isDeleted = false')
             ->setParameter('type', $usertype)
             ->orderBy('ua.user', 'asc')
        ;

        return $queryBuilder
            ->getQuery()
            ->getResult()
        ;
    }

    public function deleteByUser(User $user, ?Usertype $right = null, ?string $commissionCode = null): void
    {
        $qb = $this->createQueryBuilder('ua')
            ->delete()
            ->where('ua.user = :user')
            ->setParameter('user', $user)
        ;
        if ($right instanceof Usertype) {
            $qb
                ->andWhere('ua.userType = :right')
                ->setParameter('right', $right)
            ;
            if ($commissionCode) {
                $qb
                    ->andWhere('ua.params like :commissionCode')
                    ->setParameter('commissionCode', 'commission:' . $commissionCode)
                ;
            }
        }

        $qb
            ->getQuery()
            ->execute()
        ;
    }

    public function listAllUsersByRoleAndCommission(Usertype $usertype, string $commissionCode)
    {
        $queryBuilder = $this->createQueryBuilder('ua')
            ->innerJoin('ua.user', 'u')
            ->innerJoin('ua.userType', 't')
            ->where('ua.userType = :type')
            ->andWhere('u.isDeleted = false')
            ->andWhere('ua.params LIKE :commissionCode')
            ->setParameter('type', $usertype)
            ->setParameter('commissionCode', 'commission:' . $commissionCode)
            ->orderBy('t.hierarchie', 'desc')
            ->addOrderBy('u.lastname', 'asc')
            ->addOrderBy('u.firstname', 'asc')
        ;

        return $queryBuilder
            ->getQuery()
            ->getResult()
        ;
    }
}
