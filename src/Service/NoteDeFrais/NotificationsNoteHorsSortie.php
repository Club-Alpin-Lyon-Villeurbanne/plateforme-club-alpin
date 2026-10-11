<?php

namespace App\Service\NoteDeFrais;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Mailer\Mailer;
use App\Repository\ExpenseReportStatusHistoryRepository;
use App\Service\ExpenseReportCalculator;
use App\Utils\FileUploader;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Mails du circuit d'une note hors sortie aux valideurs et à l'adresse de la trésorerie, en plus
 * du mail de changement de statut envoyé au demandeur.
 */
class NotificationsNoteHorsSortie
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly ExpenseReportCalculator $calculator,
        private readonly ControlesNoteHorsSortie $controles,
        private readonly ValideursNoteHorsSortie $valideurs,
        private readonly ExpenseReportStatusHistoryRepository $historique,
        private readonly FileUploader $fileUploader,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'NOTES_DE_FRAIS_EMAIL_TRESORERIE')]
        private readonly string $adresseTresorerie,
    ) {
    }

    /**
     * La note attend une validation : chaque valideur reçoit son mail, ou la trésorerie s'il n'y
     * en a pas. Jamais le choix entre don et remboursement, qui ne doit pas peser sur la décision.
     */
    public function noteEnvoyee(ExpenseReport $note): void
    {
        $montants = $this->calculator->montantsNoteHorsSortie($note);
        $contexte = [
            'demandeur' => $note->getUser()?->getFullName(),
            'type' => $note->getType()->libelle(),
            'commission' => $note->getCommission()?->getTitle(),
            'objet' => $note->getObjet(),
            'lienFormation' => $note->getLienFormation(),
            'montant' => $this->calculator->formatEuros($montants['totalDeclare']),
            'partRemboursable' => $this->calculator->partRemboursable($montants),
            'mot' => $note->getMotPourResponsable(),
            'avertissements' => $this->controles->avertissements($note),
            'lien' => $this->lien($note),
        ];

        $valideurs = $this->valideurs->pour($note);
        if ([] === $valideurs) {
            $this->envoyerALaTresorerie($note, 'transactional/note-de-frais-a-valider', $contexte);

            return;
        }

        foreach ($valideurs as $valideur) {
            $autres = array_filter($valideurs, static fn (User $autre) => $autre->getId() !== $valideur->getId());
            $this->mailer->send($valideur, 'transactional/note-de-frais-a-valider', $contexte + [
                'prenom' => $valideur->getFirstname(),
                'autresValideurs' => array_values(array_map(static fn (User $autre) => $autre->getFullName(), $autres)),
            ]);
        }
    }

    /**
     * La note validée part à la trésorerie avec tout ce qu'il faut pour la payer, ou préparer le
     * reçu fiscal d'un don, sans rouvrir la note.
     */
    public function noteValidee(ExpenseReport $note): void
    {
        $montants = $this->calculator->montantsNoteHorsSortie($note);
        $decision = $this->historique->derniereDecision($note);
        $demandeur = $note->getUser();
        $dateDepense = $note->getDateDepense()?->format('d/m/Y');

        $this->envoyerALaTresorerie($note, 'transactional/note-de-frais-validee', [
            'demandeur' => $demandeur?->getFullName(),
            'type' => $note->getType()->libelle(),
            'commission' => $note->getCommission()?->getTitle(),
            'objet' => $note->getObjet(),
            'libelleDate' => $note->getType()->libelleDeLaDate(),
            'dateDepense' => $dateDepense,
            'lienFormation' => $note->getLienFormation(),
            'lignes' => $this->lignes($note),
            'total' => $this->calculator->formatEuros($montants['totalDeclare']),
            'remboursable' => $this->calculator->formatEuros($montants['totalRemboursable']),
            'avertissements' => $this->controles->avertissements($note),
            'remboursement' => $note->isRefundRequired(),
            'valideePar' => $decision?->getChangedBy()?->getFullName(),
            'valideeLe' => $decision?->getChangedAt()?->format('d/m/Y'),
            'libelleSaisie' => sprintf('%s %s NDF %s %s', mb_strtoupper((string) $demandeur?->getLastname()), $demandeur?->getFirstname(), $dateDepense, $note->getObjet()),
            'lien' => $this->lien($note),
        ]);
    }

    private function envoyerALaTresorerie(ExpenseReport $note, string $gabarit, array $contexte): void
    {
        if ('' === trim($this->adresseTresorerie)) {
            $this->logger->error(sprintf('Note de frais %d : la trésorerie n\'est pas prévenue, NOTES_DE_FRAIS_EMAIL_TRESORERIE est vide.', $note->getId()));

            return;
        }

        $this->mailer->send($this->adresseTresorerie, $gabarit, $contexte);
    }

    /**
     * Lignes dans l'ordre de la saisie, chacune avec l'adresse de son justificatif.
     *
     * @return list<array{id: ?string, nature: ?string, libelle: ?string, detail: ?string, montant: string, justificatif: ?string}>
     */
    private function lignes(ExpenseReport $note): array
    {
        $justificatifs = [];
        foreach ($note->getAttachments() as $justificatif) {
            if (null !== $justificatif->getUser()) {
                $justificatifs[$justificatif->getExpenseId()] = $this->fileUploader->getUserUploadUrl($justificatif->getUser(), $justificatif->getFileName(), 'expense-attachments');
            }
        }

        return array_map(fn (array $ligne) => array_merge($ligne, [
            'montant' => $this->calculator->formatEuros($ligne['montant']),
            'justificatif' => $justificatifs[$ligne['id'] ?? ''] ?? null,
        ]), $this->calculator->lignesAffichees($note));
    }

    private function lien(ExpenseReport $note): string
    {
        return $this->urlGenerator->generate('notes_de_frais_detail', ['id' => $note->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
