<?php

namespace App\EventListener\Entity;

use App\Entity\ExpenseReport;
use App\Mailer\Mailer;
use App\Service\ExpenseReportCalculator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::onFlush)]
class ExpenseReportStatusChangeSubscriber
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly ExpenseReportCalculator $calculator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getObjectManager();
        /** @var UnitOfWork $uow */
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof ExpenseReport) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);

            if (!isset($changeSet['status']) || !$entity->getUser()) {
                continue;
            }

            $oldStatus = $changeSet['status'][0];
            $newStatus = $changeSet['status'][1];

            if ($oldStatus === $newStatus) {
                continue;
            }

            $params = ['report' => $entity];
            $detailsArray = json_decode($entity->getDetails(), true);
            $summary = $this->calculator->calculateTotal($detailsArray);

            $params = array_merge($params, [
                'details' => $detailsArray,
                'summary' => $summary,
                'formattedTotal' => $this->calculator->formatEuros($summary['total']),
                'formattedReimbursable' => $this->calculator->formatEuros($summary['reimbursable']),
                'tauxKilometriqueVoiture' => $this->calculator->getTauxKilometriqueVoiture(),
                'tauxKilometriqueMinibus' => $this->calculator->getTauxKilometriqueMinibus(),
                'status' => $newStatus,
            ]);

            try {
                $this->mailer->send(
                    $entity->getUser(),
                    'transactional/expense-report-status-email',
                    $params
                );
            } catch (\Exception $exception) {
                $this->logger->error('Impossible d\'envoyer l\'email de note de frais');
                $this->logger->error($exception->getMessage());
            }
        }
    }
}
