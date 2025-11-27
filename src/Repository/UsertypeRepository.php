<?php

namespace App\Repository;

use App\Entity\Usertype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method Usertype|null find($id, $lockMode = null, $lockVersion = null)
 * @method Usertype|null findOneBy(array $criteria, array $orderBy = null)
 * @method Usertype[]    findAll()
 * @method Usertype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsertypeRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usertype::class);
    }

    public function getByCode(string $code): Usertype
    {
        if (null === $userType = $this->findOneByCode($code)) {
            throw new \InvalidArgumentException(sprintf('Unknown user type "%s".', $code));
        }

        return $userType;
    }

    public function findOneByCode(string $code): ?Usertype
    {
        return $this->createQueryBuilder('ut')
            ->where('ut.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllManageable()
    {
        return $this->createQueryBuilder('ut')
            ->where('ut.code not in (:codes)')
            ->setParameter('codes', ['visiteur', 'adherent'])
            ->orderBy('ut.hierarchie', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
    }

    public function findLowerResps(int $level, bool $limitedToComm = true): array
    {
        $qb = $this->createQueryBuilder('ut')
            ->where('ut.hierarchie < :level')
            ->setParameter('level', $level)
            ->orderBy('ut.hierarchie', 'DESC')
        ;
        if ($limitedToComm) {
            $qb->andWhere('ut.limitedToComm = true');
        }

        return $qb->getQuery()->getResult();
    }
}
