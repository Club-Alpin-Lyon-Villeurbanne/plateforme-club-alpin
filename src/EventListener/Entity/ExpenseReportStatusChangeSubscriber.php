<?php

namespace App\EventListener\Entity;

use App\Entity\ExpenseReport;
use App\Mailer\Mailer;
use App\Service\ExpenseReportCalculator;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

#[AsDoctrineListener(event: Events::onFlush)]
class ExpenseReportStatusChangeSubscriber
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly ExpenseReportCalculator $calculator,
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

            if (!isset($changeSet['status'])) {
                continue;
            }

            if (!$entity->getUser()) {
                continue;
            }

            $oldStatus = $changeSet['status'][0];
            $newStatus = $changeSet['status'][1];

            if ($oldStatus === $newStatus) {
                continue;
            }

            // Use delay stamp to let entitymanager to commit the transaction

            switch (true) {
                case $newStatus === ExpenseReportStatusEnum::SUBMITTED->value:
                    // On calcule ici le résumé de la note de frais
                    $detailsArray = json_decode($entity->getDetails(), true);
                    $summary = $this->calculator->calculateTotal($detailsArray);
                    // On peut formater si nécessaire
                    $formattedTotal = $this->calculator->formatEuros($summary['total']);
                    $formattedReimbursable = $this->calculator->formatEuros($summary['reimbursable']);

                    $tauxVoiture = $this->calculator->getTauxKilometriqueVoiture();
                    $tauxMinibus = $this->calculator->getTauxKilometriqueMinibus();

                    // On transmet tout au Mailer
                    $this->mailer->send(
                        $entity->getUser(),
                        'transactional/expense-report-submitted--to-submitter',
                        [
                            'report' => $entity,
                            'details' => $detailsArray,
                            'summary' => $summary,
                            'formattedTotal' => $formattedTotal,
                            'formattedReimbursable' => $formattedReimbursable,
                            'tauxKilometriqueVoiture' => $tauxVoiture,
                            'tauxKilometriqueMinibus' => $tauxMinibus,
                        ]
                    );
                    break;
                case $newStatus === ExpenseReportStatusEnum::REJECTED->value:
                    $this->mailer->send($entity->getUser(), 'transactional/expense-report-rejected--to-submitter', ['report' => $entity]);
                    break;
                case $newStatus === ExpenseReportStatusEnum::APPROVED->value:
                    $this->mailer->send($entity->getUser(), 'transactional/expense-report-approved--to-submitter', ['report' => $entity]);
                    break;
                default:
                    break;
            }
        }
    }
}
