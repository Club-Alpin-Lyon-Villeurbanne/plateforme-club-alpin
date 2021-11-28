<?php

namespace App\Repository;

use App\Entity\CafUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method CafUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method CafUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method CafUser[]    findAll()
 * @method CafUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CafUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CafUser::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof CafUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findUserByEmail(string $email): ?CafUser
    {
        return $this->createQueryBuilder('u')
            ->select('u')
            ->where('LOWER(u.emailUser) = LOWER(:email)')
            ->setParameter('email', trim($email))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
