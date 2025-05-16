<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidExpenseReportDetailsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidExpenseReportDetails) {
            throw new UnexpectedTypeException($constraint, ValidExpenseReportDetails::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_array($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        $usedExpenseIds = [];

        // Validate transport
        if (!isset($value['transport']) || !\is_array($value['transport'])) {
            $this->context->buildViolation('Transport details are missing or invalid.')
                ->addViolation();
        } else {
            $this->validateTransport($value['transport']);
        }

        // Validate accommodations
        if (!isset($value['accommodations']) || !\is_array($value['accommodations'])) {
            $this->context->buildViolation('Accommodations details are missing or invalid.')
                ->addViolation();
        } else {
            foreach ($value['accommodations'] as $index => $accommodation) {
                $this->validateAccommodation($accommodation, $index, $usedExpenseIds);
            }
        }

        // Validate other expenses
        if (!isset($value['others']) || !\is_array($value['others'])) {
            $this->context->buildViolation('Other expenses details are missing or invalid.')
                ->addViolation();
        } else {
            foreach ($value['others'] as $index => $expense) {
                $this->validateOtherExpense($expense, $index, $usedExpenseIds);
            }
        }
    }

    private function validateTransport(array $transport): void
    {
        $requiredFields = ['type', 'amount'];
        foreach ($requiredFields as $field) {
            if (!isset($transport[$field])) {
                $this->context->buildViolation("Transport {$field} is missing.")
                    ->addViolation();
            }
        }

        if (isset($transport['type'])) {
            $validTypes = ['PERSONAL_VEHICLE', 'CLUB_MINIBUS', 'RENTAL_MINIBUS', 'PUBLIC_TRANSPORT'];
            if (!\in_array($transport['type'], $validTypes, true)) {
                $this->context->buildViolation('Invalid transport type.')
                    ->addViolation();
            }
        }
    }

    private function validateAccommodation(array $accommodation, int $index, array &$usedExpenseIds): void
    {
        $requiredFields = ['expenseId', 'price'];
        foreach ($requiredFields as $field) {
            if (!isset($accommodation[$field])) {
                $this->context->buildViolation("Accommodation {$field} is missing.")
                    ->atPath("accommodations[{$index}].{$field}")
                    ->addViolation();
            }
        }

        if (isset($accommodation['expenseId'])) {
            if (\in_array($accommodation['expenseId'], $usedExpenseIds, true)) {
                $this->context->buildViolation('ExpenseId must be unique across all expenses.')
                    ->atPath("accommodations[{$index}].expenseId")
                    ->addViolation();
            } else {
                $usedExpenseIds[] = $accommodation['expenseId'];
            }
        }
    }

    private function validateOtherExpense(array $expense, int $index, array &$usedExpenseIds): void
    {
        $requiredFields = ['expenseId', 'description', 'amount'];
        foreach ($requiredFields as $field) {
            if (!isset($expense[$field])) {
                $this->context->buildViolation("Other expense {$field} is missing.")
                    ->atPath("others[{$index}].{$field}")
                    ->addViolation();
            }
        }

        if (isset($expense['expenseId'])) {
            if (\in_array($expense['expenseId'], $usedExpenseIds, true)) {
                $this->context->buildViolation('ExpenseId must be unique across all expenses.')
                    ->atPath("others[{$index}].expenseId")
                    ->addViolation();
            } else {
                $usedExpenseIds[] = $expense['expenseId'];
            }
        }
    }
}
