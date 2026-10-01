<?php

namespace App\Validator;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;
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

        if (ExpenseReportStatusEnum::SUBMITTED === $value->getStatus()) {
            $this->detailsValidator->validate($value, $this->context);
        }
    }
}
