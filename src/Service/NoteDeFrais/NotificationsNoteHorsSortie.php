<?php

namespace App\Service\NoteDeFrais;

use App\Entity\ExpenseReport;
use App\Mailer\Mailer;
use App\Service\ExpenseReportCalculator;
use App\Utils\Enums\ExpenseReportTypeEnum;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Mails du circuit d'une note hors sortie, en plus du mail de changement de statut envoyé au
 * demandeur.
 */
class NotificationsNoteHorsSortie
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly ExpenseReportCalculator $calculator,
        private readonly ControlesNoteHorsSortie $controles,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'NOTES_DE_FRAIS_EMAIL_TRESORERIE')]
        private readonly string $adresseTresorerie,
    ) {
    }

    /**
     * La note attend une validation : la trésorerie la reçoit, sans le choix entre don et
     * remboursement, qui ne doit pas peser sur la décision.
     */
    public function noteEnvoyee(ExpenseReport $note): void
    {
        if ('' === trim($this->adresseTresorerie)) {
            $this->logger->error(sprintf('Note de frais %d envoyée sans prévenir la trésorerie : NOTES_DE_FRAIS_EMAIL_TRESORERIE est vide.', $note->getId()));

            return;
        }

        $this->mailer->send($this->adresseTresorerie, 'transactional/note-de-frais-a-valider', [
            'demandeur' => $note->getUser()?->getFullName(),
            'type' => ExpenseReportTypeEnum::FORMATION === $note->getType() ? 'formation' : 'achat',
            'commission' => $note->getCommission()?->getTitle(),
            'objet' => $note->getObjet(),
            'montant' => $this->calculator->formatEuros($this->calculator->montantsNoteHorsSortie($note)['totalDeclare']),
            'mot' => $note->getMotPourResponsable(),
            'avertissements' => $this->controles->avertissements($note),
        ]);
    }
}
