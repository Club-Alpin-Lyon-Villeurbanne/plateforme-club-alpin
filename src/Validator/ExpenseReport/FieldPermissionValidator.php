<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Security\ExpenseReportActor;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Qui peut modifier quel champ d'une note de frais existante.
 *
 * - details / refundRequired : le propriétaire, tant que la note est en brouillon ou rejetée ;
 * - statusComment : un gestionnaire (non propriétaire), dans la requête où il change le statut.
 *
 * Renvoyer la valeur déjà enregistrée n'est pas une modification.
 */
class FieldPermissionValidator
{
    public function __construct(
        private readonly ExpenseReportOriginalState $originalState,
        private readonly ExpenseReportActor $actor,
    ) {
    }

    public function validate(ExpenseReport $expenseReport, ExpenseReportStatusEnum $oldStatus, ExecutionContextInterface $context): void
    {
        $canEditClaim = $this->actor->isOwnerOf($expenseReport) && $oldStatus->isEditableByOwner();

        if (!$canEditClaim && $this->changed($expenseReport, 'details', $expenseReport->getDetails())) {
            $context->buildViolation('Details can only be modified by their owner while the expense report is a draft or rejected.')
                ->atPath('details')
                ->addViolation();
        }

        if (!$canEditClaim && $this->changed($expenseReport, 'refundRequired', $expenseReport->isRefundRequired())) {
            $context->buildViolation('The refund choice can only be modified by its owner while the expense report is a draft or rejected.')
                ->atPath('refundRequired')
                ->addViolation();
        }

        // La validité de la transition elle-même est contrôlée par StatusTransitionValidator :
        // un commentaire accompagnant une transition refusée est refusé avec elle.
        $canComment = $this->actor->isManagerOf($expenseReport) && $oldStatus !== $expenseReport->getStatus();

        if (!$canComment && $this->changed($expenseReport, 'statusComment', $expenseReport->getStatusComment())) {
            $context->buildViolation('The status comment can only be set by an expense manager when changing the status.')
                ->atPath('statusComment')
                ->addViolation();
        }
    }

    private function changed(ExpenseReport $expenseReport, string $field, mixed $newValue): bool
    {
        return $this->originalState->value($expenseReport, $field) !== $newValue;
    }
}
