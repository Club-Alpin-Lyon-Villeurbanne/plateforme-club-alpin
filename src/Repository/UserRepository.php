<?php

namespace App\Repository;

use App\Entity\AlertType;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
        $this->getEntityManager()->getConfiguration()->addCustomHydrationMode('HYDRATE_LEGACY', 'App\Utils\LegacyHydrator');
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->select('u')
            ->where('LOWER(u.email) = LOWER(:email)')
            ->setParameter('email', trim($email))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByLicenseNumber(string $licenseNumber, ?string $hydratorMode = null)
    {
        return $this->createQueryBuilder('u')
            ->where('u.cafnum = :licenseNumber')
            ->setParameter('licenseNumber', $licenseNumber)
            ->getQuery()
            ->getOneOrNullResult($hydratorMode)
        ;
    }

    public function findUsersIdWithAlert(string $commissionCode, AlertType $type)
    {
        $sql = <<<SQL
            SELECT id FROM
            (
                SELECT
                    id_user as id,
                    JSON_EXTRACT(alerts, '$."$commissionCode".$type->name') as res
                FROM caf_user u
                WHERE
                    u.alerts IS NOT NULL
                    AND u.is_deleted = FALSE
                    AND u.valid_user = 1
                    AND u.email_user IS NOT NULL
                    AND u.email_user != ''
                    AND u.nomade_user = 0
            ) as sub_query
            WHERE
                sub_query.res = TRUE
SQL;

        foreach ($this->getEntityManager()->getConnection()->fetchAllAssociative($sql) as $user) {
            yield $user['id'];
        }
    }

    public function getFiliations(User $user)
    {
        if (!$user->getCafnum()) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->where('u.cafnumParent = :cafNum')
            ->setParameter('cafNum', $user->getCafnum())
            ->getQuery()
            ->getResult()
        ;
    }
}
