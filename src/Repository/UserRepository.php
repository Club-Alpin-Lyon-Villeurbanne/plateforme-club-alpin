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
                    AND u.doit_renouveler_user = 0
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

    public function blockExpiredAccounts(): void
    {
        $lastYear = (new \DateTime())->modify('-1 year')->format('Y');
        $expiryDate = strtotime("$lastYear-08-31");
        $tenDaysAgo = strtotime('-10 days');

        $qb = $this->createQueryBuilder('u');

        $qb->update()
            ->set('u.doitRenouveler', ':shouldRenew')
            ->where('u.id != :adminId')
            ->andWhere('u.nomade = :isNomade')
            ->andWhere('u.manuelUser = :isManual')
            ->andWhere(
                $qb->expr()->orX(
                    'u.dateAdhesion <= :expiryDate',
                    'u.tsUpdate <= :tenDaysAgo'
                )
            )
            ->setParameters([
                'shouldRenew' => true,
                'adminId' => 1,
                'isNomade' => false,
                'isManual' => false,
                'expiryDate' => $expiryDate,
                'tenDaysAgo' => $tenDaysAgo,
            ])
            ->getQuery()
            ->execute();
    }

    public function removeExpiredFiliations(): void
    {
        $expiryDate = new \DateTime('-200 days');

        $qb = $this->createQueryBuilder('u')
            ->update()
            ->set('u.cafnumParent', 'NULL')
            ->where('u.tsUpdate < :expiryDate')
            ->setParameter('expiryDate', $expiryDate->getTimestamp());

        try {
            $qb->getQuery()->execute();
        } catch (\Exception $exc) {
            \Sentry\captureException($exc);
        }
    }

    public function findDuplicateUser(string $lastname, string $firstname, string $birthday, string $excludeCafnum): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.lastname = :lastname')
            ->andWhere('u.firstname = :firstname')
            ->andWhere('u.birthday = :birthday')
            ->andWhere('u.cafnum != :excludeCafnum')
            ->andWhere('u.isDeleted = false')
            ->orderBy('u.tsInsert', 'DESC')
            ->setMaxResults(1)
            ->setParameters([
                'lastname' => $lastname,
                'firstname' => $firstname,
                'dayStart' => $dayStart,
                'dayEnd' => $dayEnd,
                'excludeCafnum' => $excludeCafnum,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
