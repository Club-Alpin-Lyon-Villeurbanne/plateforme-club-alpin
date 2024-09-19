<?php

namespace App\Validator;

use App\Entity\ExpenseReport;
use App\Utils\Enums\ExpenseReportStatusEnum;
use App\Validator\ExpenseReport\DetailsImmutabilityValidator;
use App\Validator\ExpenseReport\DetailsValidator;
use App\Validator\ExpenseReport\StatusTransitionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidExpenseReportValidator extends ConstraintValidator
{
    private $statusTransitionValidator;
    private $detailsImmutabilityValidator;
    private $detailsValidator;

    public function __construct(
        StatusTransitionValidator $statusTransitionValidator,
        DetailsImmutabilityValidator $detailsImmutabilityValidator,
        DetailsValidator $detailsValidator
    ) {
        $this->statusTransitionValidator = $statusTransitionValidator;
        $this->detailsImmutabilityValidator = $detailsImmutabilityValidator;
        $this->detailsValidator = $detailsValidator;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidExpenseReport) {
            throw new UnexpectedTypeException($constraint, ValidExpenseReport::class);
        }

        if (!$value instanceof ExpenseReport) {
            throw new UnexpectedTypeException($value, ExpenseReport::class);
        }

        if (ExpenseReportStatusEnum::DRAFT === $value->getStatus()) {
            return;
        }

        $this->statusTransitionValidator->validate($value, $this->context);

        if (\in_array($value->getStatus(), [ExpenseReportStatusEnum::SUBMITTED, ExpenseReportStatusEnum::APPROVED], true)) {
            $this->detailsImmutabilityValidator->validate($value, $this->context);
        }

        if (ExpenseReportStatusEnum::SUBMITTED === $value->getStatus()) {
            $this->detailsValidator->validate($value, $this->context);
        }
    }
}
