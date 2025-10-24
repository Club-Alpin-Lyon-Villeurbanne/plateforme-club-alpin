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

    // Delete all notifications sent for evcents or articles older than a year
    public function deleteExpiredNotifications()
    {
        $date = new \DateTime('-1 year');

        $sql = '
            DELETE
            FROM caf_user_notification
            WHERE type = "Sortie"
            AND entity_id IN (
                SELECT id_evt
                FROM caf_evt
                WHERE
                    status_evt = 1
                    AND end_date < \'' . $date->format('Y-m-d') . '\'
            )
        ';

        $this->_em->getConnection()->executeQuery($sql);

        $sql = '
            DELETE
            FROM caf_user_notification
            WHERE type = "Article"
            AND entity_id IN (
                SELECT id_article
                FROM caf_article
                WHERE
                    status_article = 1
                    AND validation_date < \'' . $date->format('Y-m-d') . '\'
            )
        ';

        $this->_em->getConnection()->executeQuery($sql);
    }

    public function deleteByUser(User $user): void
    {
        $this->createQueryBuilder('un')
            ->delete()
            ->where('un.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute()
        ;
    }
}
