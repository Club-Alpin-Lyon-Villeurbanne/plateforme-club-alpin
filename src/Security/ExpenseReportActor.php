<?php

namespace App\Security;

use App\Entity\ExpenseReport;
use App\Entity\User;
use App\Security\Voter\ExpenseReportVoter;
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
}
