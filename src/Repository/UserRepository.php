<?php

namespace App\Repository;

use App\Entity\AlertType;
use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\EventParticipation;
use App\Entity\Evt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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

    public function getFiliations(UserInterface $user)
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

    public function getNomads(UserInterface $user)
    {
        return $this->createQueryBuilder('u')
            ->where('u.valid = true')
            ->andWhere('u.nomade = true')
            ->andWhere('u.isDeleted = false')
            ->andWhere('u.nomadeParent = :user')
            ->setParameter('user', $user)
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->addOrderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function blockExpiredAccounts(int $expiryDate): int
    {
        $qb = $this->createQueryBuilder('u');
        $expirationDate = (new \DateTimeImmutable())->setTimestamp($expiryDate);

        $qb->update()
            ->set('u.doitRenouveler', ':shouldRenew')
            ->where('u.id != :adminId')
            ->andWhere('u.nomade = :isNomade')
            ->andWhere('u.manuelUser = :isManual')
            ->andWhere('u.joinDate <= :expiryDate')
            ->setParameters([
                'shouldRenew' => true,
                'adminId' => 1,
                'isNomade' => false,
                'isManual' => false,
                'expiryDate' => $expirationDate,
            ]);

        return $qb->getQuery()->execute();
    }

    public function removeExpiredFiliations(): int
    {
        $expiryDate = new \DateTime('-200 days');

        $qb = $this->createQueryBuilder('u')
            ->update()
            ->set('u.cafnumParent', 'NULL')
            ->where('u.updatedAt < :expiryDate')
            ->setParameter('expiryDate', $expiryDate)
        ;

        try {
            return $qb->getQuery()->execute();
        } catch (\Exception $exc) {
            \Sentry\captureException($exc);

            return 0;
        }
    }

    public function findDuplicateUser(string $lastname, string $firstname, \DateTimeImmutable $birthday, string $excludeCafnum): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('LOWER(u.lastname) = LOWER(:lastname)')
            ->andWhere('LOWER(u.firstname) = LOWER(:firstname)')
            ->andWhere('u.birthdate = :birthday')
            ->andWhere('u.cafnum != :excludeCafnum')
            ->andWhere('u.isDeleted = false')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameters([
                'lastname' => $lastname,
                'firstname' => $firstname,
                'birthday' => $birthday,
                'excludeCafnum' => $excludeCafnum,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUsersToRegister(array $participants, string $show = 'valid')
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u NOT IN (:users)')
            ->andWhere('u.isDeleted = false')
            ->setParameter('users', $participants)
        ;
        if ('valid' === $show) {
            $qb
                ->andWhere('u.doitRenouveler = false')
                ->andWhere('u.nomade = false')
            ;
        }
        $qb
            ->orderBy('u.lastname', 'asc')
            ->addOrderBy('u.firstname', 'asc')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findUsersToDelete(?\DateTime $end = null)
    {
        $qb = $this
            ->createQueryBuilder('u')
            ->leftJoin(Article::class, 'a', Join::WITH, 'u.id = a.user')
            ->leftJoin(Comment::class, 'c', Join::WITH, 'u.id = c.user')
            ->leftJoin(Evt::class, 'e', Join::WITH, 'u.id = e.user')
            ->leftJoin(EventParticipation::class, 'p', Join::WITH, 'u.id = p.user')
            ->where('u.isDeleted = false')
            ->andWhere('u.id != 1')     // super admin
            ->andWhere('a.id is null')
            ->andWhere('c.id is null')
            ->andWhere('e.id is null')
            ->andWhere('p.id is null')
        ;
        if (null !== $end) {
            $qb
                ->andWhere('u.joinDate <= :end')
                ->setParameter('end', $end)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function findUsersToAnonymize(?\DateTime $end = null)
    {
        $qb = $this
            ->createQueryBuilder('u')
            ->leftJoin(Article::class, 'a', Join::WITH, 'u.id = a.user')
            ->leftJoin(Comment::class, 'c', Join::WITH, 'u.id = c.user')
            ->leftJoin(Evt::class, 'e', Join::WITH, 'u.id = e.user')
            ->leftJoin(EventParticipation::class, 'p', Join::WITH, 'u.id = p.user')
            ->where('u.isDeleted = false')
            ->andWhere('u.id != 1')     // super admin
            ->andWhere('(a.id is not null or c.id is not null or e.id is not null or p.id is not null)')
        ;
        if (null !== $end) {
            $qb
                ->andWhere('u.joinDate <= :end')
                ->setParameter('end', $end)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function anonymizeUser(User $user): void
    {
        $this
            ->createQueryBuilder('u')
            ->update()
            ->set('u.isDeleted', true)
            ->set('u.email', ':nullValue')
            ->set('u.mdp', ':nullValue')
            ->set('u.cafnum', ':nullValue')
            ->set('u.cafnumParent', ':nullValue')
            ->set('u.firstname', ':firstname')
            ->set('u.lastname', ':lastname')
            ->set('u.nickname', ':nickname')
            ->set('u.tel', ':nullValue')
            ->set('u.tel2', ':nullValue')
            ->set('u.adresse', ':nullValue')
            ->set('u.cp', ':nullValue')
            ->set('u.ville', ':nullValue')
            ->set('u.pays', ':nullValue')
            ->set('u.moreinfo', ':nullValue')
            ->set('u.valid', ':falseValue')
            ->set('u.cookietoken', ':nullValue')
            ->set('u.doitRenouveler', ':falseValue')
            ->set('u.alerteRenouveler', ':falseValue')
            ->set('u.alerts', ':nullValue')
            ->set('u.alertSortiePrefix', ':nullValue')
            ->set('u.alertArticlePrefix', ':nullValue')
            ->set('u.updatedAt', ':updatedAt')
            ->where('u.id = :user')
            ->setParameter('user', $user)
            ->setParameter('nullValue', null)
            ->setParameter('falseValue', false)
            ->setParameter('firstname', 'compte')
            ->setParameter('lastname', 'supprimé ' . $user->getId())
            ->setParameter('nickname', 'Csuppr' . $user->getId())
            ->setParameter('updatedAt', (new \DateTime())->format('Y-m-d H:i:s'))
            ->getQuery()
            ->execute()
        ;
    }
}
