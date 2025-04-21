<?php

namespace App\EventListener;

use App\Entity\ExpenseReport;
use App\Entity\ExpenseReportStatusHistory;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ExpenseReportStatusListener
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ExpenseReport) {
            return;
        }

        if (!$args->hasChangedField('status')) {
            return;
        }

        $oldStatus = $args->getOldValue('status');
        $newStatus = $args->getNewValue('status');

        try {
            if (is_string($oldStatus)) {
                $oldStatus = ExpenseReportStatusEnum::from($oldStatus);
            }
            if (is_string($newStatus)) {
                $newStatus = ExpenseReportStatusEnum::from($newStatus);
            }
        } catch (\ValueError $e) {
            throw new \RuntimeException('Statut de note de frais invalide lors du changement de statut.', 0, $e);
        }

        if ($oldStatus === $newStatus) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw new \RuntimeException('Impossible de déterminer l\'utilisateur lors du changement de statut de la note de frais.');
        }

        $history = new ExpenseReportStatusHistory();
        $history->setExpenseReport($entity);
        $history->setOldStatus($oldStatus);
        $history->setNewStatus($newStatus);
        $history->setChangedBy($user);
        $history->setChangedAt(new \DateTimeImmutable());

        $this->entityManager->persist($history);
        // Pas de flush ici, Doctrine le fera après le cycle
    }
} 