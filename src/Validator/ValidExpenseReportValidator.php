<?php

namespace App\Validator;

use App\Entity\ExpenseReport;
use App\Service\NoteDeFrais\ControlesNoteHorsSortie;
use App\Service\NoteDeFrais\MomentDeControle;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Validator\ExpenseReport\CoherenceValidator;
use App\Validator\ExpenseReport\DetailsValidator;
use App\Validator\ExpenseReport\ExpenseReportOriginalState;
use App\Validator\ExpenseReport\FieldPermissionValidator;
use App\Validator\ExpenseReport\StatusTransitionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidExpenseReportValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ExpenseReportOriginalState $originalState,
        private readonly StatusTransitionValidator $statusTransitionValidator,
        private readonly FieldPermissionValidator $fieldPermissionValidator,
        private readonly DetailsValidator $detailsValidator,
        private readonly CoherenceValidator $coherenceValidator,
        private readonly ControlesNoteHorsSortie $controles,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidExpenseReport) {
            throw new UnexpectedTypeException($constraint, ValidExpenseReport::class);
        }

        if (!$value instanceof ExpenseReport) {
            throw new UnexpectedTypeException($value, ExpenseReport::class);
        }

        $oldStatus = null;

        // Une note existante passe toujours par les contrôles de droits, quel que soit le statut demandé.
        if (null !== $value->getId()) {
            $oldStatus = $this->originalState->status($value);

            if (null === $oldStatus) {
                // État d'origine inconnu : on refuse plutôt que de supposer un brouillon.
                $this->context->buildViolation('The original state of the expense report cannot be determined.')
                    ->atPath('status')
                    ->addViolation();

                return;
            }

            $this->statusTransitionValidator->validate($value, $oldStatus, $this->context);
            $this->fieldPermissionValidator->validate($value, $oldStatus, $this->context);
        }

        $this->coherenceValidator->validate($value, $this->context);

        if (null === $value->getId()) {
            $this->appliquerLesControles($value, MomentDeControle::CREATION);
        }

        if (ExpenseReportStatusEnum::SUBMITTED !== $value->getStatus()) {
            return;
        }

        if (!$value->getType()->estHorsSortie()) {
            $this->detailsValidator->validate($value, $this->context);

            return;
        }

        // Une note hors sortie est contrôlée à son envoi ; envoyée, elle n'est plus modifiable.
        $moments = match ($oldStatus) {
            ExpenseReportStatusEnum::DRAFT => [MomentDeControle::CHAQUE_SOUMISSION, MomentDeControle::PREMIERE_SOUMISSION],
            ExpenseReportStatusEnum::REJECTED => [MomentDeControle::CHAQUE_SOUMISSION],
            default => [],
        };
        $this->appliquerLesControles($value, ...$moments);
    }

    private function appliquerLesControles(ExpenseReport $note, MomentDeControle ...$moments): void
    {
        foreach ($this->controles->bloquants($note, ...$moments) as $constat) {
            $this->context->buildViolation($constat['message'])
                ->atPath($constat['champ'])
                ->addViolation();
        }
    }
}
