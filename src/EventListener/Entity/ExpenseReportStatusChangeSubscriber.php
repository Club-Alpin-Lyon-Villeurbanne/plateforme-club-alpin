<?php

namespace App\EventListener\Entity;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Mailer\Mailer;
use App\Service\ExpenseReportCalculator;
use App\Service\NoteDeFrais\ValideursNoteHorsSortie;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsDoctrineListener(event: Events::onFlush)]
class ExpenseReportStatusChangeSubscriber
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly ExpenseReportCalculator $calculator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
        private readonly ValideursNoteHorsSortie $valideurs,
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

            if ($entity->getType()->estHorsSortie()) {
                $this->mailer->send($entity->getUser(), 'transactional/expense-report-status-email', $this->parametresNoteHorsSortie($entity));

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

            // Générer l'URL absolue de la sortie si elle existe
            if ($entity->getEvent()) {
                $params['event_url'] = $this->urlGenerator->generate(
                    'sortie',
                    ['code' => $entity->getEvent()->getCode(), 'id' => $entity->getEvent()->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            $this->mailer->send(
                $entity->getUser(),
                'transactional/expense-report-status-email',
                $params
            );
        }
    }

    private function parametresNoteHorsSortie(ExpenseReport $note): array
    {
        $montants = $this->calculator->montantsNoteHorsSortie($note);
        // L'auteur du changement, celui que l'historique des statuts enregistre.
        $auteur = $this->security->getUser();

        return [
            'report' => $note,
            'auteur' => $auteur instanceof User ? $auteur->getFullName() : null,
            'lienCorrection' => $this->urlGenerator->generate('notes_de_frais_nouvelle', ['id' => $note->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'status' => $note->getStatus()->value,
            // À qui la note est envoyée ; vide : à la trésorerie.
            'valideurs' => array_map(static fn (User $valideur) => $valideur->getFullName(), $this->valideurs->pour($note)),
            'lignes' => array_map(fn (array $ligne) => array_merge($ligne, ['montant' => $this->calculator->formatEuros($ligne['montant'])]), $this->calculator->lignesAffichees($note)),
            'formattedTotal' => $this->calculator->formatEuros($montants['totalDeclare']),
            'partRemboursable' => $this->calculator->partRemboursable($montants),
        ];
    }
}
