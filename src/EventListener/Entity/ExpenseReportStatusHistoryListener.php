<?php

namespace App\EventListener\Entity;

use App\Entity\ExpenseReport;
use App\Entity\ExpenseReportStatusHistory;
use App\Entity\User;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Historise chaque changement de statut d'une note de frais (qui, quand, de quoi vers quoi).
 *
 * Écrit en onFlush : une entité persistée en preUpdate n'est jamais insérée par Doctrine.
 * Priorité supérieure à ExpenseReportStatusChangeSubscriber : si l'auteur est inconnu, le flush
 * échoue avant que le mail de changement de statut ne soit mis en file (hors transaction).
 */
#[AsDoctrineListener(event: Events::onFlush, priority: 10)]
class ExpenseReportStatusHistoryListener
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof ExpenseReport) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            if (!isset($changeSet['status'])) {
                continue;
            }

            [$oldStatus, $newStatus] = array_map($this->toStatus(...), $changeSet['status']);
            if ($oldStatus === $newStatus) {
                continue;
            }

            $user = $this->security->getUser();
            if (!$user instanceof User) {
                throw new \RuntimeException('Impossible de déterminer l\'utilisateur lors du changement de statut de la note de frais.');
            }

            $history = (new ExpenseReportStatusHistory())
                ->setExpenseReport($entity)
                ->setOldStatus($oldStatus)
                ->setNewStatus($newStatus)
                ->setChangedBy($user)
                ->setChangedAt(new \DateTimeImmutable());

            $em->persist($history);
            $uow->computeChangeSet($em->getClassMetadata(ExpenseReportStatusHistory::class), $history);
        }
    }

    /**
     * Doctrine lit les propriétés enum via EnumReflectionProperty, qui renvoie la valeur
     * scalaire : le changeset contient des chaînes, pas des enums.
     */
    private function toStatus(ExpenseReportStatusEnum|string|null $value): ?ExpenseReportStatusEnum
    {
        return \is_string($value) ? ExpenseReportStatusEnum::from($value) : $value;
    }
}
