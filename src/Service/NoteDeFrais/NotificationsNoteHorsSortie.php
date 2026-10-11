<?php

namespace App\Service\NoteDeFrais;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Mailer\Mailer;
use App\Repository\ExpenseReportStatusHistoryRepository;
use App\Service\ExpenseReportCalculator;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tous les mails d'une note hors sortie : au demandeur et aux valideurs. Aucun ne part à la
 * trésorerie, qui suit les notes dans compta-club.
 */
class NotificationsNoteHorsSortie
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly ExpenseReportCalculator $calculator,
        private readonly ControlesNoteHorsSortie $controles,
        private readonly ValideursNoteHorsSortie $valideurs,
        private readonly ExpenseReportStatusHistoryRepository $historique,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * À appeler une fois le nouveau statut enregistré, pour que l'historique nomme l'auteur de la
     * décision.
     */
    public function statutChange(ExpenseReport $note): void
    {
        $valideurs = [];
        if (ExpenseReportStatusEnum::SUBMITTED === $note->getStatus()) {
            $valideurs = $this->noteEnvoyee($note);
        }

        $this->prevenirLeDemandeur($note, $valideurs);
    }

    /**
     * La note attend une validation : chaque valideur reçoit son mail. Jamais le choix entre don et
     * remboursement, qui ne doit pas peser sur la décision. Une réponse au mail va au demandeur.
     *
     * @return list<User> les valideurs ; aucun si la note va directement à la trésorerie
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
        foreach ($valideurs as $valideur) {
            $autres = array_filter($valideurs, static fn (User $autre) => $autre->getId() !== $valideur->getId());
            $this->mailer->send($valideur, 'transactional/note-de-frais-a-valider', $contexte + [
                'prenom' => $valideur->getFirstname(),
                'autresValideurs' => array_values(array_map(static fn (User $autre) => $autre->getFullName(), $autres)),
            ], replyTo: $note->getUser());
        }

        return $valideurs;
    }

    /**
     * @param list<User> $valideurs ceux à qui la note envoyée est transmise ; aucun : la trésorerie
     */
    private function prevenirLeDemandeur(ExpenseReport $note, array $valideurs): void
    {
        $demandeur = $note->getUser();
        if (null === $demandeur) {
            return;
        }

        $montants = $this->calculator->montantsNoteHorsSortie($note);
        $decision = \in_array($note->getStatus(), [ExpenseReportStatusEnum::PREAPPROVED, ExpenseReportStatusEnum::REJECTED], true)
            ? $this->historique->derniereDecision($note)
            : null;

        $this->mailer->send($demandeur, 'transactional/expense-report-status-email', [
            'report' => $note,
            'auteur' => $decision?->getChangedBy()?->getFullName(),
            'lienCorrection' => $this->urlGenerator->generate('notes_de_frais_nouvelle', ['id' => $note->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'status' => $note->getStatus()->value,
            'valideurs' => array_map(static fn (User $valideur) => $valideur->getFullName(), $valideurs),
            'lignes' => $this->lignes($note),
            'formattedTotal' => $this->calculator->formatEuros($montants['totalDeclare']),
            'partRemboursable' => $this->calculator->partRemboursable($montants),
        ]);
    }

    /**
     * Lignes dans l'ordre de la saisie.
     *
     * @return list<array{id: ?string, nature: ?string, libelle: ?string, detail: ?string, montant: string}>
     */
    private function lignes(ExpenseReport $note): array
    {
        return array_map(fn (array $ligne) => array_merge($ligne, [
            'montant' => $this->calculator->formatEuros($ligne['montant']),
        ]), $this->calculator->lignesAffichees($note));
    }

    private function lien(ExpenseReport $note): string
    {
        return $this->urlGenerator->generate('notes_de_frais_detail', ['id' => $note->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
