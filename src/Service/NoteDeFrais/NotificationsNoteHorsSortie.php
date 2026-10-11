<?php

namespace App\Service\NoteDeFrais;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Mailer\Mailer;
use App\Repository\ExpenseReportStatusHistoryRepository;
use App\Service\ExpenseReportCalculator;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Utils\FileUploader;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tous les mails d'une note hors sortie : au demandeur, aux valideurs et à l'adresse de la
 * trésorerie. Le mail au demandeur nomme ceux qui ont réellement reçu la note.
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
     * À appeler une fois le nouveau statut enregistré, pour que l'historique nomme l'auteur de la
     * décision.
     */
    public function statutChange(ExpenseReport $note): void
    {
        $valideursPrevenus = [];
        if (ExpenseReportStatusEnum::SUBMITTED === $note->getStatus()) {
            $valideursPrevenus = $this->noteEnvoyee($note);
        } elseif (ExpenseReportStatusEnum::APPROVED === $note->getStatus()) {
            $this->noteValidee($note);
        }

        $this->prevenirLeDemandeur($note, $valideursPrevenus);
    }

    /**
     * La note attend une validation : chaque valideur joignable reçoit son mail, ou la trésorerie
     * si aucun ne l'est. Jamais le choix entre don et remboursement, qui ne doit pas peser sur la
     * décision. Une réponse au mail va au demandeur.
     *
     * @return list<User> les valideurs prévenus ; aucun si la note est partie à la trésorerie
     */
    private function noteEnvoyee(ExpenseReport $note): array
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
        $joignables = array_values(array_filter($valideurs, fn (User $valideur) => $this->mailer->isValid($valideur->getEmail())));
        if ([] === $joignables) {
            if ([] !== $valideurs) {
                $this->logger->error(sprintf('Note de frais %d : aucun valideur n\'a d\'adresse mail valide, la note à valider part à la trésorerie.', $note->getId()));
            }
            $this->envoyerALaTresorerie($note, 'transactional/note-de-frais-a-valider', $contexte);

            return [];
        }

        foreach ($joignables as $valideur) {
            $autres = array_filter($joignables, static fn (User $autre) => $autre->getId() !== $valideur->getId());
            $this->mailer->send($valideur, 'transactional/note-de-frais-a-valider', $contexte + [
                'prenom' => $valideur->getFirstname(),
                'autresValideurs' => array_values(array_map(static fn (User $autre) => $autre->getFullName(), $autres)),
            ], replyTo: $note->getUser());
        }

        return $joignables;
    }

    /**
     * La note validée part à la trésorerie avec tout ce qu'il faut pour la payer, ou préparer le
     * reçu fiscal d'un don, sans rouvrir la note.
     */
    private function noteValidee(ExpenseReport $note): void
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

    /**
     * @param list<User> $valideursPrevenus aucun : la note envoyée est partie à la trésorerie
     */
    private function prevenirLeDemandeur(ExpenseReport $note, array $valideursPrevenus): void
    {
        $demandeur = $note->getUser();
        if (null === $demandeur) {
            return;
        }

        $montants = $this->calculator->montantsNoteHorsSortie($note);
        $decision = \in_array($note->getStatus(), [ExpenseReportStatusEnum::APPROVED, ExpenseReportStatusEnum::REJECTED], true)
            ? $this->historique->derniereDecision($note)
            : null;

        $this->mailer->send($demandeur, 'transactional/expense-report-status-email', [
            'report' => $note,
            'auteur' => $decision?->getChangedBy()?->getFullName(),
            'lienCorrection' => $this->urlGenerator->generate('notes_de_frais_nouvelle', ['id' => $note->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'status' => $note->getStatus()->value,
            'valideurs' => array_map(static fn (User $valideur) => $valideur->getFullName(), $valideursPrevenus),
            'lignes' => $this->lignes($note),
            'formattedTotal' => $this->calculator->formatEuros($montants['totalDeclare']),
            'partRemboursable' => $this->calculator->partRemboursable($montants),
        ]);
    }

    private function envoyerALaTresorerie(ExpenseReport $note, string $gabarit, array $contexte): void
    {
        if ('' === trim($this->adresseTresorerie)) {
            $this->logger->error(sprintf('Note de frais %d : la trésorerie n\'est pas prévenue, NOTES_DE_FRAIS_EMAIL_TRESORERIE est vide.', $note->getId()));

            return;
        }

        $this->mailer->send($this->adresseTresorerie, $gabarit, $contexte, replyTo: $note->getUser());
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
