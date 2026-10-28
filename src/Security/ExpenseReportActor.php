<?php

namespace App\Security;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Security\Voter\ExpenseReportVoter;
use App\Security\Voter\ResponsableNoteDeFraisVoter;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Rôle de l'utilisateur connecté vis-à-vis d'une note de frais.
 */
class ExpenseReportActor
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function isOwnerOf(ExpenseReport $expenseReport): bool
    {
        $currentUser = $this->security->getUser();
        $owner = $expenseReport->getUser();

        return $currentUser instanceof User
            && null !== $owner
            && null !== $currentUser->getId()
            && $owner->getId() === $currentUser->getId();
    }

    /**
     * Gestionnaire des notes de frais ET non propriétaire de celle-ci : un gestionnaire
     * propriétaire est traité comme un simple propriétaire, personne ne valide sa propre dépense.
     */
    public function isManagerOf(ExpenseReport $expenseReport): bool
    {
        return !$this->isOwnerOf($expenseReport)
            && $this->security->isGranted(ExpenseReportVoter::MANAGE_EXPENSE_REPORTS);
    }

    /**
     * Valideur d'une note hors sortie : responsable de sa commission, et pas son demandeur. Un
     * gestionnaire peut l'être aussi : il agit en valideur tant que la note est envoyée.
     */
    public function isResponsableOf(ExpenseReport $expenseReport): bool
    {
        return !$this->isOwnerOf($expenseReport)
            && $this->security->isGranted(ResponsableNoteDeFraisVoter::RESPONSABLE_DE_LA_NOTE, $expenseReport);
    }

    /**
     * Le choix entre don et remboursement ne doit pas peser sur la décision du responsable : seuls
     * le demandeur et la trésorerie le connaissent.
     */
    public function voitLeChoixDuRemboursement(ExpenseReport $expenseReport): bool
    {
        return $this->isOwnerOf($expenseReport) || $this->isManagerOf($expenseReport);
    }
}
