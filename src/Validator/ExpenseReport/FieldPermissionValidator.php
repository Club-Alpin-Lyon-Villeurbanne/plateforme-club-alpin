<?php

namespace App\Validator\ExpenseReport;

use App\Entity\ExpenseReport;
use App\Security\ExpenseReportActor;
use App\Utils\Enums\ExpenseReportStatusEnum;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Qui peut modifier quel champ d'une note de frais existante.
 *
 * - details / refundRequired, et les champs d'une note hors sortie (ExpenseReport::champsHorsSortie()) :
 *   le propriétaire, tant que la note est en brouillon ou rejetée ;
 * - statusComment : un gestionnaire (non propriétaire) ou le responsable de la note, dans la
 *   requête où il change le statut.
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
        $proprietaire = $this->actor->isOwnerOf($expenseReport);
        $canEditClaim = $proprietaire && $oldStatus->isEditableByOwner();
        // Le formulaire du site reconnaît ce texte (onglet resté ouvert après l'envoi) et passe à l'état « envoyée ».
        $refus = $proprietaire
            ? 'Cette note a déjà été envoyée : elle ne peut plus être modifiée.'
            : 'Seul le demandeur peut modifier cette note.';

        $champs = ['details' => $expenseReport->getDetails(), 'refundRequired' => $expenseReport->isRefundRequired(), ...$expenseReport->champsHorsSortie()];
        foreach ($champs as $champ => $valeur) {
            if (!$canEditClaim && $this->changed($expenseReport, $champ, $valeur)) {
                $context->buildViolation($refus)
                    ->atPath($champ)
                    ->addViolation();
            }
        }

        // La validité de la transition elle-même est contrôlée par StatusTransitionValidator :
        // un commentaire accompagnant une transition refusée est refusé avec elle.
        $canComment = ($this->actor->isManagerOf($expenseReport) || $this->actor->isResponsableOf($expenseReport))
            && $oldStatus !== $expenseReport->getStatus();

        if (!$canComment && $this->changed($expenseReport, 'statusComment', $expenseReport->getStatusComment())) {
            $context->buildViolation('Seul un gestionnaire ou un responsable de la commission peut écrire ce commentaire, en changeant le statut de la note.')
                ->atPath('statusComment')
                ->addViolation();
        }
    }

    private function changed(ExpenseReport $expenseReport, string $field, mixed $newValue): bool
    {
        $originalValue = $this->originalState->value($expenseReport, $field);

        // La même date renvoyée arrive dans un nouvel objet.
        if ($originalValue instanceof \DateTimeInterface && $newValue instanceof \DateTimeInterface) {
            return $originalValue->format('Y-m-d') !== $newValue->format('Y-m-d');
        }

        return $originalValue !== $newValue;
    }
}
