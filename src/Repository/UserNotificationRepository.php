<?php

namespace App\Repository;

use App\Entity\AlertType;
use App\Entity\User;
use App\Entity\UserNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserNotification[]    findAll()
 * @method UserNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotification::class);
    }

    public function hasNotificationBeSent(User $user, AlertType $type, string|int $entityId): bool
    {
        $sql = 'SELECT id
            FROM caf_user_notification
            WHERE signature = :signature';

        return false !== $this->_em->getConnection()->fetchOne($sql, ['signature' => UserNotification::generateSignature($user, $type, $entityId)]);
    }
}
