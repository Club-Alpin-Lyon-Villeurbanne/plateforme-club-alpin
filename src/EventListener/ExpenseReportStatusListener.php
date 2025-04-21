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

        // On ne log que si le statut change réellement
        if ($oldStatus === $newStatus) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user) {
            return;
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