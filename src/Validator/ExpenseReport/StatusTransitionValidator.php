<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Security\ExpenseReportActor;
use App\Service\NoteDeFrais\ValideursNoteHorsSortie;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class StatusTransitionValidator
{
    /**
     * Le propriétaire ne peut que soumettre (ou resoumettre après un rejet).
     */
    private const OWNER_TRANSITIONS = [
        ExpenseReportStatusEnum::DRAFT->value => [ExpenseReportStatusEnum::SUBMITTED],
        ExpenseReportStatusEnum::REJECTED->value => [ExpenseReportStatusEnum::SUBMITTED],
    ];

    /**
     * Un gestionnaire, sur la note de sortie d'un autre, décide puis comptabilise.
     * ACCOUNTED est un état terminal.
     */
    private const MANAGER_TRANSITIONS = [
        ExpenseReportStatusEnum::SUBMITTED->value => [ExpenseReportStatusEnum::APPROVED, ExpenseReportStatusEnum::REJECTED],
        ExpenseReportStatusEnum::APPROVED->value => [ExpenseReportStatusEnum::ACCOUNTED],
    ];

    /**
     * La trésorerie, sur une note hors sortie, décide après le responsable puis comptabilise. Une
     * note envoyée sans valideur lui revient dès l'envoi (voir managerTransitions()).
     */
    private const MANAGER_TRANSITIONS_HORS_SORTIE = [
        ExpenseReportStatusEnum::PREAPPROVED->value => [ExpenseReportStatusEnum::APPROVED, ExpenseReportStatusEnum::REJECTED],
        ExpenseReportStatusEnum::APPROVED->value => [ExpenseReportStatusEnum::ACCOUNTED],
    ];

    /**
     * Un valideur d'une note hors sortie la pré-valide pour la trésorerie, ou la renvoie.
     */
    private const VALIDEUR_TRANSITIONS = [
        ExpenseReportStatusEnum::SUBMITTED->value => [ExpenseReportStatusEnum::PREAPPROVED, ExpenseReportStatusEnum::REJECTED],
    ];

    private const DECISIONS = [ExpenseReportStatusEnum::PREAPPROVED, ExpenseReportStatusEnum::APPROVED, ExpenseReportStatusEnum::REJECTED];

    public function __construct(
        private readonly ExpenseReportActor $actor,
        private readonly ValideursNoteHorsSortie $valideurs,
        private readonly ExpenseReportOriginalState $originalState,
    ) {
    }

    public function validate(ExpenseReport $expenseReport, ExpenseReportStatusEnum $oldStatus, ExecutionContextInterface $context): void
    {
        $newStatus = $expenseReport->getStatus();
        $allowedTargets = $this->allowedTargets($expenseReport, $oldStatus);

        // Plusieurs personnes décident d'une même note : celle qui arrive quand la note a quitté son
        // étape doit l'apprendre, même si elle demande la décision déjà prise.
        if ($this->dejaTraitee($expenseReport, $oldStatus, $newStatus, $allowedTargets)) {
            $context->buildViolation('Cette note a déjà été traitée.')
                ->atPath('status')
                ->addViolation();

            return;
        }

        if ($oldStatus === $newStatus) {
            return;
        }

        if (!\in_array($newStatus, $allowedTargets, true)) {
            $context->buildViolation($this->motifDuRefus($expenseReport, $oldStatus, $newStatus))
                ->atPath('status')
                ->addViolation();

            return;
        }

        // Une note hors sortie renvoyée doit dire au demandeur quoi corriger, dans la requête même
        // du renvoi : le mot laissé à la pré-validation n'en tient pas lieu. Facultatif pour une sortie.
        if ($expenseReport->getType()->estHorsSortie()
            && ExpenseReportStatusEnum::REJECTED === $newStatus
            && !$this->messageEcritAvecLeRenvoi($expenseReport)) {
            $context->buildViolation('Un message au demandeur est obligatoire pour refuser la note.')
                ->atPath('statusComment')
                ->addViolation();
        }
    }

    /**
     * Une décision sur une note hors sortie qui a déjà reçu une décision, demandée par qui n'a plus
     * rien à y décider. Le demandeur ne décide pas : il reçoit le refus ordinaire.
     *
     * @param list<ExpenseReportStatusEnum> $allowedTargets
     */
    private function dejaTraitee(ExpenseReport $expenseReport, ExpenseReportStatusEnum $oldStatus, ExpenseReportStatusEnum $newStatus, array $allowedTargets): bool
    {
        return $expenseReport->getType()->estHorsSortie()
            && \in_array($newStatus, self::DECISIONS, true)
            && \in_array($oldStatus, [...self::DECISIONS, ExpenseReportStatusEnum::ACCOUNTED], true)
            && [] === array_filter($allowedTargets, static fn (ExpenseReportStatusEnum $target) => \in_array($target, self::DECISIONS, true))
            && !$this->actor->isOwnerOf($expenseReport);
    }

    /**
     * Affiché tel quel par le site et par compta-club.
     */
    private function motifDuRefus(ExpenseReport $note, ExpenseReportStatusEnum $oldStatus, ExpenseReportStatusEnum $newStatus): string
    {
        if ($this->actor->isOwnerOf($note) && \in_array($newStatus, [...self::DECISIONS, ExpenseReportStatusEnum::ACCOUNTED], true)) {
            return 'Vous ne pouvez pas décider sur votre propre note de frais.';
        }

        // La trésorerie a pu ouvrir la note quand elle n'avait pas de valideur : la synchronisation
        // de la nuit a pu, depuis, rendre son responsable de nouveau actif.
        if ($note->getType()->estHorsSortie()
            && ExpenseReportStatusEnum::SUBMITTED === $oldStatus
            && $this->actor->isManagerOf($note)
            && !$this->actor->isResponsableOf($note)
            && [] !== $this->valideurs->pour($note)) {
            return 'Cette note attend encore la décision du responsable de la commission.';
        }

        return 'Ce changement de statut n\'est pas permis.';
    }

    /**
     * @return list<ExpenseReportStatusEnum>
     */
    private function allowedTargets(ExpenseReport $expenseReport, ExpenseReportStatusEnum $oldStatus): array
    {
        if ($this->actor->isOwnerOf($expenseReport)) {
            return self::OWNER_TRANSITIONS[$oldStatus->value] ?? [];
        }

        // Un gestionnaire qui est aussi valideur agit en valideur tant que la note est envoyée :
        // la trésorerie n'y a rien à décider, puisque la note a un valideur.
        $allowedTargets = [];
        if ($this->actor->isResponsableOf($expenseReport)) {
            $allowedTargets = self::VALIDEUR_TRANSITIONS[$oldStatus->value] ?? [];
        }

        if ($this->actor->isManagerOf($expenseReport)) {
            $allowedTargets = [...$allowedTargets, ...$this->managerTransitions($expenseReport, $oldStatus)];
        }

        return $allowedTargets;
    }

    /**
     * @return list<ExpenseReportStatusEnum>
     */
    private function managerTransitions(ExpenseReport $note, ExpenseReportStatusEnum $oldStatus): array
    {
        if (!$note->getType()->estHorsSortie()) {
            return self::MANAGER_TRANSITIONS[$oldStatus->value] ?? [];
        }

        if (ExpenseReportStatusEnum::SUBMITTED === $oldStatus) {
            return [] === $this->valideurs->pour($note) ? [ExpenseReportStatusEnum::APPROVED, ExpenseReportStatusEnum::REJECTED] : [];
        }

        return self::MANAGER_TRANSITIONS_HORS_SORTIE[$oldStatus->value] ?? [];
    }

    private function messageEcritAvecLeRenvoi(ExpenseReport $note): bool
    {
        $message = $note->getStatusComment();

        return '' !== trim((string) $message) && $message !== $this->originalState->value($note, 'statusComment');
    }
}
